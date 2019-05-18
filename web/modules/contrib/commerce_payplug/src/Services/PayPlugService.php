<?php

namespace Drupal\commerce_payplug\Services;

use Payplug\Payplug;

/**
 * PayPlug encapsulation service class.
 *
 * This class can be injected in requiring class, thus allow PayPlug service
 * unit/functional testing.
 *
 * @group commerce_payplug
 */
class PayPlugService implements PayPlugServiceInterface {

  protected $api_key;

  /**
   * { @inheritdoc }
   */
  public function setApiKey($api_key) {
    $this->api_key = $api_key;
  }

  /**
   * { @inheritdoc }
   */
  public function createPayPlugPayment(array $data, Payplug $payplug = null) {
    Payplug::setSecretKey($this->api_key);
    $payment = \Payplug\Payment::create($data, $payplug);
    return $payment;
  }

  /**
   * { @inheritdoc }
   */
  public function treatPayPlugNotification($notification, $authentication = null) {
    Payplug::setSecretKey($this->api_key);
    $resource = \Payplug\Notification::treat($notification, $authentication = null);
    return $resource;
  }
}