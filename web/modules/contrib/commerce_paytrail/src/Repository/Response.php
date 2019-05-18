<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail\Repository;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_paytrail\Exception\InvalidValueException;
use Drupal\commerce_paytrail\Exception\SecurityHashMismatchException;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

/**
 * Defines the data type for paytrail response.
 */
class Response extends BaseResource {

  protected $order;
  protected $orderNumber;
  protected $paymetMethod;
  protected $paymentId;
  protected $redirectKey;
  protected $remoteId;
  protected $authCode;
  protected $status;
  protected $timestamp;

  /**
   * Constructs a new instance.
   *
   * @param string $merchantHash
   *   The merchant hash.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function __construct(string $merchantHash, OrderInterface $order) {
    $this->order = $order;

    parent::__construct($merchantHash);
  }

  /**
   * Sets the order number.
   *
   * @param string $orderNumber
   *   The order number.
   *
   * @return $this
   */
  public function setOrderNumber(string $orderNumber) : self {
    $this->orderNumber = $orderNumber;
    return $this;
  }

  /**
   * Sets the return auth code.
   *
   * @param string $code
   *   The return auth code.
   *
   * @return $this
   *   The self.
   */
  public function setAuthCode(string $code) : self {
    $this->authCode = $code;
    return $this;
  }

  /**
   * Sets the payment method.
   *
   * @param string $method
   *   The payment method.
   *
   * @return $this
   *   The self.
   */
  public function setPaymentMethod(string $method) : self {
    $this->paymetMethod = $method;
    return $this;
  }

  /**
   * Sets the unix timestamp.
   *
   * @param int $timestamp
   *   The unix timestamp.
   *
   * @return $this
   *   The self.
   */
  public function setTimestamp(int $timestamp) : self {
    $this->timestamp = $timestamp;
    return $this;
  }

  /**
   * Creates a new instance from the current request.
   *
   * @param string $merchantHash
   *   The merchant hash.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return $this
   *   The self.
   */
  public static function createFromRequest(string $merchantHash, OrderInterface $order, Request $request) : self {
    $required = [
      'ORDER_NUMBER',
      'PAYMENT_ID',
      'PAYMENT_METHOD',
      'TIMESTAMP',
      'STATUS',
      'RETURN_AUTHCODE',
    ];

    foreach ($required as $key) {
      if (!$value = $request->query->get($key)) {
        throw new InvalidValueException(sprintf('Value for %s not found', $key));
      }
    }
    return (new static($merchantHash, $order))
      ->setAuthCode($request->query->get('RETURN_AUTHCODE'))
      ->setOrderNumber($request->query->get('ORDER_NUMBER'))
      ->setPaymentId($request->query->get('PAYMENT_ID'))
      ->setPaymentMethod($request->query->get('PAYMENT_METHOD'))
      ->setTimestamp((int) $request->query->get('TIMESTAMP'))
      ->setPaymentStatus($request->query->get('STATUS'));
  }

  /**
   * Sets the payment status.
   *
   * @param string $status
   *   The payment status.
   *
   * @return $this
   *   The self.
   */
  public function setPaymentStatus(string $status) : self {
    Assert::oneOf($status, ['PAID', 'CANCELLED']);
    $this->status = $status;

    return $this;
  }

  /**
   * Sets the payment id.
   *
   * @param string $paymentId
   *   The payment id.
   *
   * @return $this
   *   The self.
   */
  public function setPaymentId(string $paymentId) : self {
    $this->paymentId = $paymentId;
    return $this;
  }

  /**
   * Gets the payment status.
   *
   * @return string
   *   The payment status.
   */
  public function getPaymentStatus() : string {
    return $this->status;
  }

  /**
   * Gets the auth code.
   *
   * @return string
   *   The auth code.
   */
  public function getAuthCode() : string {
    return $this->authCode;
  }

  /**
   * Gets the payment method.
   *
   * @return string
   *   The payment method.
   */
  public function getPaymentMethod() : string {
    return $this->paymetMethod;
  }

  /**
   * Gets the order number.
   *
   * @return string
   *   The order number.
   */
  public function getOrderNumber() : string {
    return $this->orderNumber;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() : OrderInterface {
    return $this->order;
  }

  /**
   * Gets the payment id.
   *
   * @return string
   *   The payment id.
   */
  public function getPaymentId() : string {
    return $this->paymentId;
  }

  /**
   * Gets the unix timestamp.
   *
   * @return int
   *   The timestamp.
   */
  public function getTimestamp() : int {
    return $this->timestamp;
  }

  /**
   * Converts the values to array.
   *
   * @return array
   *   The array of values.
   */
  public function getHashValues() : array {
    return [
      'ORDER_NUMBER' => $this->getOrderNumber(),
      'PAYMENT_ID' => $this->getPaymentId(),
      'PAYMENT_METHOD' => $this->getPaymentMethod(),
      'TIMESTAMP' => $this->getTimestamp(),
      'STATUS' => $this->getPaymentStatus(),
    ];
  }

  /**
   * Checks if given response is valid.
   *
   * @throws \Drupal\commerce_paytrail\Exception\SecurityHashMismatchException
   */
  public function isValidResponse() : void {
    $hash_values = $this->getHashValues();

    // Make sure payment status is paid.
    if ($this->getPaymentStatus() !== 'PAID') {
      throw new SecurityHashMismatchException('payment_state', 'Validation failed (invalid payment state)');
    }

    // Make sure we have a valid order number and it matches the one given
    // to the Paytrail.
    if ((string) $this->order->id() !== $this->getOrderNumber()) {
      throw new SecurityHashMismatchException('order_number', 'Validation failed (order number mismatch)');
    }

    if ($this->generateReturnChecksum($hash_values) !== $this->getAuthCode()) {
      throw new SecurityHashMismatchException('hash_mismatch', 'Validation failed (security hash mismatch)');
    }
  }

}
