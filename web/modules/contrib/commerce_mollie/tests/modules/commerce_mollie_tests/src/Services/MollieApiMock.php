<?php

namespace Drupal\commerce_mollie_tests\Services;

use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\PaymentStatus as MolliePaymentStatus;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Mock class.
 *
 * This Mock passed every call to the original MollieApiClient
 * except overridden methods.
 *
 * Methods are overridden when they need a connection to the Mollie-server.
 */
class MollieApiMock {

  /**
   * MollieApiClient.
   *
   * @var \Mollie\Api\MollieApiClient
   */
  protected $mollieApiClient;

  /**
   * CommerceOrderStorage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $commerceOrderStorage;

  /**
   * MollieApiMock constructor.
   */
  public function __construct() {
    $this->mollieApiClient = new MollieApiClient();
    $this->commerceOrderStorage = \Drupal::entityTypeManager()->getStorage('commerce_order');
  }

  /**
   * Magic method that passes every _call to this same object.
   *
   * @param string $method
   *   The method to be called.
   * @param mixed $args
   *   The parameters.
   */
  public function __call($method, $args) {
    call_user_func_array([$this->mollieApiClient, $method], $args);
  }

  /**
   * Magic method that passes every _get to the parent object.
   *
   * @param string $name
   *   The property to be called.
   *
   * @return $this|string
   *   Return the parameter
   */
  public function __get($name) {

    // Overrides the 'id' parameter.
    if ($name === 'id') {
      return 'test_id';
    }
    // Overrides the 'status' parameter.
    if ($name === 'status') {
      if ($this->isPaid()) {
        return MolliePaymentStatus::STATUS_PAID;
      }
      if ($this->isCancelled()) {
        return MolliePaymentStatus::STATUS_CANCELED;
      }
      if ($this->isOpen()) {
        return MolliePaymentStatus::STATUS_OPEN;
      }
      if ($this->isFailed()) {
        return MolliePaymentStatus::STATUS_FAILED;
      }
      if ($this->isExpired()) {
        return MolliePaymentStatus::STATUS_EXPIRED;
      }
      return 'UNDEFINED';
    }
    // Pass any other __get to the parent object.
    return $this;
  }

  /**
   * Overrides the getCheckoutUrl() method.
   *
   * @return string
   *   The overridden post-url.
   */
  public function getCheckoutUrl() {
    global $base_url;
    return $base_url . '/commerce_mollie_tests/fake_mollie_post_url';
  }

  /**
   * Overrides the create() method.
   *
   * @param mixed $transaction_data
   *   Payload.
   *
   * @return $this
   *   Return itself for further processing.
   */
  public function create($transaction_data) {
    return $this;
  }

  /**
   * Overrides the get() method.
   *
   * @return $this
   *   Return itself for further processing.
   */
  public function get() {
    return $this;
  }

  /**
   * Overrides mollie_post_url for testing.
   *
   * Order of a-sync onNotify and onMollieReturn calls must be executed in the
   * test-suite.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns an empty response.
   */
  public function molliePostUrl() {
    return new JsonResponse();
  }

  /**
   * Mocks the isPaid() method for testing.
   *
   * @return bool
   *   TRUE when order-total is 29.99 USD.
   */
  public function isPaid() {
    /** @var \Drupal\commerce_order\Entity\Order $commerce_order */
    $commerce_order = $this->commerceOrderStorage->load(1);
    /** @var \Drupal\commerce_price\Price $balance */
    $balance = $commerce_order->getBalance();
    return $balance->getNumber() === '29.99';
  }

  /**
   * Mocks the isCancelled() method for testing.
   *
   * @return bool
   *   TRUE when order-total is 59.98 (2 x 29.99) USD.
   */
  public function isCancelled() {
    /** @var \Drupal\commerce_order\Entity\Order $commerce_order */
    $commerce_order = $this->commerceOrderStorage->load(1);
    /** @var \Drupal\commerce_price\Price $balance */
    $balance = $commerce_order->getBalance();
    return $balance->getNumber() === '59.98';
  }

  /**
   * Mocks the isOpen() method for testing.
   *
   * @return bool
   *   TRUE when order-total is 89.97 (3 x 29.99) USD.
   */
  public function isOpen() {
    /** @var \Drupal\commerce_order\Entity\Order $commerce_order */
    $commerce_order = $this->commerceOrderStorage->load(1);
    /** @var \Drupal\commerce_price\Price $balance */
    $balance = $commerce_order->getBalance();
    return $balance->getNumber() === '89.97';
  }

  /**
   * Mocks the isFailed() method for testing.
   *
   * @return bool
   *   TRUE when order-total is 119.96 (4 x 29.99) USD.
   */
  public function isFailed() {
    /** @var \Drupal\commerce_order\Entity\Order $commerce_order */
    $commerce_order = $this->commerceOrderStorage->load(1);
    /** @var \Drupal\commerce_price\Price $balance */
    $balance = $commerce_order->getBalance();
    return $balance->getNumber() === '119.96';
  }

  /**
   * Mocks the isExpired() method for testing.
   *
   * @return bool
   *   TRUE when order-total is 149.95 (5 x 29.99) USD.
   */
  public function isExpired() {
    /** @var \Drupal\commerce_order\Entity\Order $commerce_order */
    $commerce_order = $this->commerceOrderStorage->load(1);
    /** @var \Drupal\commerce_price\Price $balance */
    $balance = $commerce_order->getBalance();
    return $balance->getNumber() === '149.95';
  }

}
