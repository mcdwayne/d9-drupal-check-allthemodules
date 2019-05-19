<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Customer;
use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the invoice event.
 *
 * @see https://stripe.com/docs/api#invoice_object
 */
class InvoiceEvent extends Event {

  /**
   * The invoice.
   *
   * @var \Stripe\Event
   */
  protected $invoice;

  /**
   * Constructs a new InvoiceEvent.
   *
   * @param \Stripe\Event $invoice
   *   The invoice.
   */
  public function __construct(StripeEvent $invoice) {
    $this->invoice = $invoice;
  }

  /**
   * Gets the owner of the invoice.
   *
   * @return \Stripe\Customer
   *    Returns the owner of the invoice.
   */
  public function getCustomer() {
    return Customer::retrieve($this->invoice->__get('data')['object']['customer']);
  }

  /**
   * Gets the invoice.
   *
   * @return \Stripe\Event
   *   Returns the invoice.
   */
  public function getInvoice() {
    return $this->invoice;
  }

}
