<?php

namespace Drupal\commerce_vantiv\Event;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for filtering the request before sending it to Vantiv.
 *
 * @see \Drupal\commerce_vantiv\Event\VantivEvents
 */
class FilterVantivRequestEvent extends Event {

  /**
   * The payment gateway configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The request values.
   *
   * @var array
   */
  protected $request;

  /**
   * The payment entity.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * Constructs a new FilterVantivRequestEvent object.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity.
   * @param array $configuration
   *   The payment gateway configuration.
   * @param array $request
   *   The request values.
   */
  public function __construct(PaymentInterface $payment, array $configuration, array $request) {
    $this->payment = $payment;
    $this->configuration = $configuration;
    $this->request = $request;
  }

  /**
   * Gets the payment gateway configuration.
   *
   * @return array
   *   The payment gateway configuration.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Gets the payment entity.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The payment entity.
   */
  public function getPayment() {
    return $this->payment;
  }

  /**
   * Gets the request values.
   *
   * @return array
   *   The request values.
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Sets the request values.
   *
   * @param array $request
   *   The request values.
   *
   * @return $this
   */
  public function setRequest(array $request) {
    $this->request = $request;
    return $this;
  }

}
