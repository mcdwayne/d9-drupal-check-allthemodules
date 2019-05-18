<?php

namespace Drupal\commerce_partpay\PartPay;

use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * Class Payment Express Service.
 *
 * @package Drupal\commerce_partpay
 */
class PartPay extends AbstractAbstractPartPayRequest {

  /**
   * Initiate the PartPay connection deeds.
   */
  public function init() {

    if (!$this->hasToken()) {
      $this->setClientId($this->getSettings('partpayClientId'));
      $this->setSecret($this->getSettings('partpaySecret'));

      $response = $this->createToken();

      if (is_object($response) || property_exists($response, 'access_token') || property_exists($response, 'expires_in')) {
        $this->saveToken($response->access_token, $response->expires_in);
        $this->setTokenRequestMode(FALSE);
      }

    }

  }

  /**
   * Save the access token to state.
   */
  public function saveToken($token, $expiry) {
    \Drupal::state()->set('partPayToken', $token);
    \Drupal::state()->set('partPayTokenExpiry', time() + $expiry);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteTokens() {
    parent::deleteTokens();
    \Drupal::state()->delete('partPayToken');
    \Drupal::state()->delete('partPayTokenExpiry');
  }

  /**
   * Do we have a valid access token?
   */
  public function hasToken() {

    $token = \Drupal::state()->get('partPayToken');
    $expires = \Drupal::state()->get('partPayTokenExpiry');

    if (!empty($expires) && !is_numeric($expires)) {
      $expires = strtotime($expires);
    }

    $result = !empty($token) && time() < $expires;

    if ($result) {
      $this->setToken($token);
      $this->setTokenExpiry($expires);
    }

    return $result;

  }

  /**
   * Prepare the Drupal request for PartPay.
   */
  public function prepareTransaction(PaymentInterface $payment, $form) {

    $order = $payment->getOrder();

    /** @var \Drupal\address\AddressInterface $billingAddress */
    $billingAddress = $order->getBillingProfile()->get('address')->first();

    /** @var \Drupal\address\AddressInterface $shippingAddress */
    $shippingAddress = [];

    $data = [
      'amount' => number_format($payment->getAmount()->getNumber(), 2),
      'consumer' => [
        'email' => $order->getEmail(),
      ],
      'description' => sprintf('%s #%d', $this->getReference(), $order->id()),
      'merchant' => [
        'redirectConfirmUrl' => $form['#return_url'],
        'redirectCancelUrl' => $form['#cancel_url'],
      ],
      'merchantReference' => sprintf('%s #%d', $this->getReference(), $order->id()),
    ];

    if (!empty($billingAddress)) {
      $data['billing'] = [
        'addressLine1' => $billingAddress->getAddressLine1(),
        'addressLine2' => $billingAddress->getAddressLine2(),
        'suburb' => $billingAddress->getDependentLocality(),
        'city' => $billingAddress->getLocality(),
        'postcode' => $billingAddress->getPostalCode(),
        'state' => $billingAddress->getAdministrativeArea(),
      ];

      $data['consumer']['givenNames'] = $billingAddress->getGivenName();
      $data['consumer']['surname'] = $billingAddress->getFamilyName();
    }

    if (!empty($shippingAddress)) {
      $data['shipping'] = [
        'addressLine1' => $shippingAddress->getAddressLine1(),
        'addressLine2' => $shippingAddress->getAddressLine2(),
        'suburb' => $shippingAddress->getDependentLocality(),
        'city' => $shippingAddress->getLocality(),
        'postcode' => $shippingAddress->getPostalCode(),
        'state' => $shippingAddress->getAdministrativeArea(),
      ];
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface $orderItems */
    $orderItems = $order->getItems();

    $lineItems = [];

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $item */
    foreach ($orderItems as $item) {

      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchasableEntity */
      $purchasableEntity = $item->getPurchasedEntity();

      $lineItems[] = [
        "description" => sprintf("%s: %s", ucwords($purchasableEntity->bundle()), $item->getTitle()),
        "name" => $item->getTitle(),
        "sku" => $purchasableEntity->getSku(),
        "quantity" => intval($item->getQuantity()),
        "price" => number_format($item->getTotalPrice()->getNumber(), 2),
      ];
    }

    if (!empty($lineItems)) {
      $data['items'] = $lineItems;
    }

    return $data;
  }

}
