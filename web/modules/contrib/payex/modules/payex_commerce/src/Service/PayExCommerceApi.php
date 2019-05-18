<?php

namespace Drupal\payex_commerce\Service;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\payex\PayEx\PayExAPIException;
use Drupal\payex\Service\PayExApi;

/**
 * Class PayExApiWrapper
 *
 * Wrapper for the public PayEx api to make integration with commerce easier.
 */
class PayExCommerceApi {

  use StringTranslationTrait;

  /**
   * The public PayEx API class.
   *
   * @var PayExApi
   */
  protected $api;

  /**
   * The Drupal config factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PayExApiWrapper class.
   *
   * @param PayExApi $api
   *   The public PayEx API class.
   * @param ConfigFactoryInterface $configFactory
   *   The Drupal config factory.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(PayExApi $api, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->api = $api;
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Complete the PayEx Payment
   *
   * @param PaymentInterface $payment
   *   The commerce payment, must be of type payex
   * @param bool $allow_save
   *   If method should be allowed to save the updated of the payment if needed.
   *
   * @return PaymentInterface
   *   The updated
   *
   * @throws PayExAPIException
   *   If invalid payment is given, the exception will be thrown.
   */
  public function completePayment(PaymentInterface $payment, $allow_save = TRUE) {
    if ($payment->bundle() != 'payex') {
      throw new PayExAPIException('Invalid payment, can only complete PayEx payments');
    }
    $result = $this->api->complete(['orderRef' => $payment->getRemoteId()]);
    if (!$result || $result['orderStatus'] > 0) {
      return $payment;
    }

    /**
     * transactionStatus:
     *
     * 0 = Sale
     * 1 = Initialize
     * 2 = Credit
     * 3 = Authorize
     * 4 = Cancel
     * 5 = Failure
     * 6 = Capture
     */
    if (in_array($result['transactionStatus'], [0, 3, 6])) {
      $state = ($this->api->getPurchaseOperation() == 'SALE') ? 'authorize_capture' : 'authorize';
      $transition = $payment->getState()->getWorkflow()->getTransition($state);
      $payment->getState()->applyTransition($transition);
      $payment->transaction_id = $result['transactionNumber'];
      $payment->masked_cc = $result['maskedNumber'];
      $payment->card_type = $result['paymentMethod'];
      if ($allow_save) {
        $payment->save();
      }
    }
    // Cancel
    elseif ($result['transactionStatus'] == 4) {
      $payment->delete();
      return FALSE;
    }
    // Failure
    elseif ($result['transactionStatus'] == 5) {
      $payment->delete();
      return FALSE;
    }
    return $payment;
  }

  /**
   * Gets the iframe url for the order.
   *
   * @param Order $order
   *
   * @return string
   *   The full URL of the used for the iframe.
   */
  public function getIframeUrlForOrder(Order $order) {
    /** @var PaymentInterface[] $payments */
    $payments = $this->entityTypeManager->getStorage('commerce_payment')->loadByProperties(['order_id' => $order->id()]);
    if ($payments) {
      foreach ($payments as $payment) {
        if ($payment->bundle() == 'payex') {
          // Prices are the same, return URL.
          if ($payment->getAmount()->compareTo($order->getTotalPrice()) === 0) {
            return $payment->payex_redirect_url->value;
          }
          $payment->delete();
        }
      }
    }

    $site_name = $this->configFactory->get('system.site')->get('name');
    $params = [
      'purchaseOperation' => $this->api->getPurchaseOperation(),
      'price' => (int) ($order->getTotalPrice()->getNumber() * 100),
      'currency' => $order->getTotalPrice()->getCurrencyCode(),
      'orderID' => $order->getOrderNumber(),
      'clientIPAddress' => $order->getIpAddress(),
      'returnUrl' => Url::fromRoute('payex_commerce.iframe_return', ['commerce_order' => $order->id()], ['absolute' => TRUE])->toString(),
      'view' => 'CREDITCARD',
      'clientLanguage' => $this->api->getPayExLanguage(),
      'productNumber' => $order->getOrderNumber(), // We don't really know what was bought, so just use order id as this is required.
      'description' => (string) $this->t('Payment for site @site_name', ['@site_name' => $site_name]),
      'additionalValues' => 'RESPONSIVE=1',
    ];
    $result = $this->api->initialize($params);
    if (!empty($result['redirectUrl'])) {
      $payment = Payment::create([
        'type' => 'payex',
        'payment_method' => $order->payment_method->target_id,
        'payment_gateway' => $order->payment_gateway->target_id,
        'order_id' => $order->id(),
        'remote_id' => $result['orderRef'],
        'amount' => $order->getTotalPrice(),
        'payex_redirect_url' => $result['redirectUrl'],
        'test' => $this->api->getTest(),
      ]);
      $payment->save();
      return $result['redirectUrl'];
    }
    return FALSE;
  }

  /**
   * Gets the payex payment entity for the order.
   *
   * @param Order $order
   *   The order to get the payment for
   *
   * @return PaymentInterface|NULL
   *   The payment
   */
  public function getPaymentForOrder(Order $order) {
    /** @var PaymentInterface[] $payments */
    $payments = $this->entityTypeManager->getStorage('commerce_payment')->loadByProperties(['order_id' => $order->id()]);
    if ($payments) {
      foreach ($payments as $payment) {
        if ($payment->bundle() == 'payex') {
          // Prices are the same, return it.
          if ($payment->getAmount()->compareTo($order->getTotalPrice()) === 0) {
            return $payment;
          }
        }
      }
    }
    // We didn't find any matching, return NULL.
    return NULL;
  }

  /**
   * Get indication of the order has been paid or not.
   *
   * @param Order $order
   *   The commerce order
   *
   * @return bool
   *   Boolean status on weather or not the order has been paid.
   */
  public function isOrderPaid(Order $order) {
    $payment = $this->getPaymentForOrder($order);
    if ($payment) {
      return $payment->getState()->value == 'authorization' || $payment->getState()->value == 'capture_completed';
    }
    return FALSE;
  }

}
