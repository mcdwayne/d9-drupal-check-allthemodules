<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Customer;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the invoice item event.
 *
 * @see https://stripe.com/docs/api#invoiceitem_object
 */
class InvoiceItemEvent extends Event {

  /**
   * The invoice item.
   *
   * @var \Stripe\Event
   */
  protected $invoiceItem;

  /**
   * Constructs a new InvoiceItemEvent.
   *
   * @param \Stripe\Event $invoice_item
   *   The invoice item.
   */
  public function __construct(StripeEvent $invoice_item) {
    $this->invoiceItem = $invoice_item;
  }

  /**
   * Gets the owner of the invoice.
   *
   * @return \Stripe\Customer
   *    Returns the owner of the invoice.
   */
  public function getCustomer() {
    return Customer::retrieve($this->invoiceItem->__get('data')['object']['customer']);
  }

  /**
   * Gets the invoice item.
   *
   * @return \Stripe\Event
   *   Returns the invoice item.
   */
  public function getInvoiceItem() {
    return $this->invoiceItem;
  }

}
