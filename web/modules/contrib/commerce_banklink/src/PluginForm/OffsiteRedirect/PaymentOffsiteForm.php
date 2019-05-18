<?php

namespace Drupal\commerce_banklink\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $payment_configuration = $payment_gateway_plugin->getConfiguration();
    $order = $payment->getOrder();

    $redirect_url = Url::fromUri($payment_configuration['api_url'])->toString();

    $data = [
      'VK_SERVICE' => 1012,
      'VK_VERSION' => '008',
      'VK_SND_ID' => $payment_configuration['merchant_id'],
      'VK_STAMP' => $order->id(),
      'VK_AMOUNT' => \Drupal::getContainer()->get('commerce_price.rounder')->round($payment->getAmount())->getNumber(), // Wrap in str_replace() for old standard payments
      'VK_CURR' => $payment->getAmount()->getCurrencyCode(),
      'VK_REF' => $this->generateReferenceNumber($order->getCustomerId()),
      'VK_MSG' => t('Order @order_number at @store', ['@order_number' => $order->id(), '@store' => $order->getStore()->label()]),
      'VK_RETURN' => $form['#return_url'],
      'VK_CANCEL' => $form['#cancel_url'],
      'VK_DATETIME' => date('Y-m-d\TH:i:sO'),
    ];

    $data['VK_MAC'] = $this->signBanklink($data, $payment_configuration);

    $data['VK_ENCODING'] = 'UTF-8';
    $data['VK_LANG'] = 'EST';

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, 'post');
  }

  private function signBanklink($data, $config) {
    $string = '';

    foreach ($data as $element) {
      $string .= str_pad(mb_strlen($element), 3, "0", STR_PAD_LEFT) . $element;
    }

    if (empty($config['private_key'])) {
      return;
    }

    $pkeyid = @openssl_get_privatekey($config['private_key']);
    @openssl_sign($string, $signature, $pkeyid);
    $mac = base64_encode($signature);
    @openssl_free_key($pkeyid);

    return $mac;
  }

  private function generateReferenceNumber(int $number) {
    if (empty($number)) {
      return '013';
    }

    $number = '0'.$number;
    $weight = [7, 3, 1];
    $total = 0;

    for ($i = 1; $i <= strlen($number); $i++) {
      $total += substr($number, $i * (-1), 1) * $weight[($i-1)%3];
    }

    $checkNr = (ceil($total/10)*10)-$total;

    return $number.$checkNr;
  }

}
