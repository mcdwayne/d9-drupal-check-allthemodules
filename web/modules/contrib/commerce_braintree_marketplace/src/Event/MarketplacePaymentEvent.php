<?php

namespace Drupal\commerce_braintree_marketplace\Event;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines an event for resolving marketplace data for a payment.
 *
 * @package Drupal\commerce_braintree_marketplace\Event
 */
class MarketplacePaymentEvent extends Event {

  /**
   * The payment.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * Braintree transaction options.
   *
   * @var array
   */
  protected $transactionOptions = [];

  /**
   * A custom descriptor for the transaction.
   *
   * @var string|null
   */
  protected $customDescriptor = NULL;

  /**
   * @inheritDoc
   */
  public function __construct(PaymentInterface $payment, array $options) {
    if ($payment->bundle() !== 'payment_braintree_submerchant') {
      throw new \InvalidArgumentException('Payment type must be payment_braintree_submerchant');
    }
    $this->transactionOptions = $options;
    $this->payment = $payment;
  }

  /**
   * Get the custom descriptor.
   *
   * @return null|string
   */
  public function getCustomDescriptor() {
    return $this->customDescriptor;
  }

  /**
   * Set or unset the custom descriptor.
   *
   * Max length 18. Braintree will truncate if longer.
   *
   * @param null|string $customDescriptor
   */
  public function setCustomDescriptor($customDescriptor) {
    $this->customDescriptor = $customDescriptor;
  }

  /**
   * Getter for transaction options.
   *
   * @return array
   */
  public function getTransactionOptions() {
    return $this->transactionOptions;
  }

  /**
   * Setter for transaction options.
   *
   * @param array $transactionOptions
   */
  public function setTransactionOptions(array $transactionOptions) {
    $this->transactionOptions = $transactionOptions;
  }

  /**
   * Set a transaction option.
   *
   * @param string $key The array key.
   * @param mixed $data The data.
   */
  public function setTransactionOption(string $key, $data) {
    $this->transactionOptions[$key] = $data;
  }

  /**
   * Getter for the Merchant ID.
   *
   * There is no setter; the payment should contain a seller profile field.
   *
   * @return string|null
   */
  public function getMerchantId() {
    if ($this->payment->get('seller_profile')->isEmpty()) {
      return NULL;
    }
    if (!$this->payment->get('seller_profile')->first()->entity->get('braintree_id')->isEmpty()) {
      return $this->payment->get('seller_profile')->first()->entity
        ->get('braintree_id')->first()->remote_id;
    }
    return NULL;
  }

  /**
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   */
  public function getPayment() {
    return $this->payment;
  }

  /**
   * Get the order from the payment.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   */
  public function getOrder() {
    return $this->payment->getOrder();
  }

}
