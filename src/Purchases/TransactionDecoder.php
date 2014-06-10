<?php
namespace PHPSC\PagSeguro\Purchases;

use DateTime;
use PHPSC\PagSeguro\Address;
use PHPSC\PagSeguro\Customer;
use PHPSC\PagSeguro\Item;
use PHPSC\PagSeguro\Phone;
use PHPSC\PagSeguro\Shipping;
use SimpleXMLElement;
use PHPSC\PagSeguro\TransactionDetails;

class TransactionDecoder
{
    /**
     * @param SimpleXMLElement $obj
     *
     * @return Transaction
     */
    public function decode(SimpleXMLElement $obj)
    {
        return new Transaction(
            $this->createDetails($obj),
            $this->createPayment($obj),
            (int) $obj->type,
            $this->createItems($obj->items),
            $this->createShipping($obj->shipping)
        );
    }

    /**
     * @param SimpleXMLElement $obj
     * @return TransactionDetails
     */
    protected function createDetails(SimpleXMLElement $obj)
    {
        return new TransactionDetails(
            (string) $obj->code,
            isset($obj->reference) ? (string) $obj->reference : null,
            (int) $obj->status,
            new DateTime((string) $obj->date),
            new DateTime((string) $obj->lastEventDate),
            $this->createCustomer($obj->sender)
        );
    }

    /**
     * @param SimpleXMLElement $obj
     * @return PaymentDetails
     */
    protected function createPayment(SimpleXMLElement $obj)
    {
        return new PaymentDetails(
            new PaymentMethod(
                (int) $obj->paymentMethod->type,
                (int) $obj->paymentMethod->code
            ),
            (float) $obj->grossAmount,
            (float) $obj->discountAmount,
            (float) $obj->feeAmount,
            (float) $obj->netAmount,
            (float) $obj->extraAmount,
            (int) $obj->installmentCount,
            isset($obj->escrowEndDate) ? new DateTime((string) $obj->escrowEndDate) : null
        );
    }

    /**
     * @param SimpleXMLElement $itemsNode
     * @return array
     */
    protected function createItems(SimpleXMLElement $itemsNode)
    {
        $items = array();

        foreach ($itemsNode->item as $item) {
            $items[] = new Item(
                (string) $item->id,
                (string) $item->description,
                (float) $item->amount,
                (int) $item->quantity,
                isset($item->shippingCost) ? (float) $item->shippingCost : null,
                isset($item->weight) ? (int) $item->weight : null
            );
        }

        return $items;
    }

    /**
     * @param SimpleXMLElement $customer
     * @return Customer
     */
    protected function createCustomer(SimpleXMLElement $customer)
    {
        $phone = null;

        if ($customer->phone) {
            $phone = new Phone(
                (string) $customer->phone->areaCode,
                (string) $customer->phone->number
            );
        }

        return new Customer(
            (string) $customer->email,
            isset($customer->name) ? (string) $customer->name : null,
            $phone
        );
    }

    /**
     * @param SimpleXMLElement $shipping
     * @return Shipping
     */
    protected function createShipping(SimpleXMLElement $shipping)
    {
        $address = null;

        if ($shipping->address) {
            $address = new Address(
                (string) $shipping->address->state,
                (string) $shipping->address->city,
                (string) $shipping->address->postalCode,
                (string) $shipping->address->district,
                (string) $shipping->address->street,
                (string) $shipping->address->number,
                (string) $shipping->address->complement
            );
        }

        return new Shipping(
            (int) $shipping->type,
            $address,
            isset($shipping->cost) ? (float) $shipping->cost : null
        );
    }
}
