<?php

namespace Drupal\commerce_payone\Event;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event used to manipulate payone requests.
 *
 * @see \Drupal\commerce_payone\Event\CommercePayoneEvents
 */
class PayoneRequestEvent extends Event {

  /**
   * The payment which is executed.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * The request array.
   *
   * @var string
   */
  protected $request;

  /**
   * Constructs a new FilterConditionsEvent object.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment which is executed.
   * @param array $request
   *   The request array..
   */
  public function __construct(PaymentInterface $payment, array $request) {
    $this->payment = $payment;
    $this->request = $request;
  }

  /**
   * Gets the condition definitions.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The Payment for this event.
   */
  public function getPayment() {
    return $this->payment;
  }

  /**
   * Set an adjusted request.
   *
   * @param array $request
   *   The adjusted request.
   *
   * @return $this
   */
  public function setRequest(array $request) {
    $this->request = $request;
    return $this;
  }

  /**
   * Gets the current request.
   *
   * @return array
   *   The request array.
   */
  public function getRequest() {
    return $this->request;
  }

}
