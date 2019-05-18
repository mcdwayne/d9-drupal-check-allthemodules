<?php

namespace Drupal\commerce_worldline;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Calculator;
use Sips\Passphrase;
use Sips\PaymentRequest;
use Sips\ShaComposer\AllParametersShaComposer;

/**
 * Class TransformOrder.
 */
class TransformOrder {

  /**
   * Create a payment request class to be validated.
   *
   * @param string[] $config
   *   The plugin configuration.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $return_url
   *   Url we should redirect back to.
   * @param string $transaction_reference
   *   Transaction reference.
   * @param string $brand
   *   (optional) The preselected brand at the gateway (VISA, MAESTRO, ...).
   *
   * @return \Sips\PaymentRequest
   *   The Payment request to be sent to the provider.
   */
  public function toPaymentRequest(array $config, OrderInterface $order, $return_url, $transaction_reference, $brand = NULL) {
    $passphrase = new Passphrase($config['sips_passphrase']);
    $shaComposer = new AllParametersShaComposer($passphrase);

    $paymentRequest = new PaymentRequest($shaComposer);

    switch ($config['mode']) {
      case 2:
        $sips_url = PaymentRequest::PRODUCTION;
        break;

      case 1:
        $sips_url = PaymentRequest::SIMU;
        break;

      default:
        $sips_url = PaymentRequest::TEST;
        break;
    }

    $paymentRequest->setSipsUri($sips_url);
    $paymentRequest->setMerchantId($config['sips_merchant_id']);
    $paymentRequest->setKeyVersion($config['sips_key_version']);

    $paymentRequest->setNormalReturnUrl($return_url);
    $paymentRequest->setTransactionReference($transaction_reference);

    // Set an amount in cents.
    $cents = Calculator::multiply($order->getTotalPrice()->getNumber(), '100', 0);
    $paymentRequest->setAmount(intval($cents));
    $paymentRequest->setCurrency($order->getTotalPrice()->getCurrencyCode());

    $language_code = $order->language()->getId();

    // If the order language is not in one of the SIPS allowed languages, it
    // will fall back to english.
    if (!in_array($language_code, $paymentRequest->allowedlanguages)) {
      $language_code = 'en';
    }

    $paymentRequest->setLanguage($language_code);

    // Passing a brand along will make the gateway already preselect a brand on
    // the payment page. Not passing that brand along will make the gateway
    // present you with a selection of enabled brands.
    if ($brand !== NULL) {
      $paymentRequest->setPaymentBrand($brand);
    }

    return $paymentRequest;
  }

}
