<?php

namespace Drupal\commerce_webpay\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webpay\Entity\WebpayConfig;
use Drupal\webpay\WebpayNormalService;
use Drupal\commerce_payment\Entity\Payment;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $payment = $this->entity;
    $orderId = $payment->getOrderId();
    $amount = $payment->getAmount()->getNumber();
    $paymentGateway = $payment->getPaymentGateway();
    $payment_gateway_plugin = $paymentGateway->getPlugin();
    $webpay_config_id = $payment_gateway_plugin->getConfiguration()['webpay_config'];

    if (!($webpay_config = WebpayConfig::load($webpay_config_id))) {
      throw new \InvalidArgumentException('The webpay config does not exist.');
    }

    $payment = Payment::create([
      'state' => 'new',
      'amount' => $payment->getAmount(),
      'payment_gateway' => $paymentGateway->id(),
      'order_id' => $orderId,
    ]);
    $payment->save();

    $webpayService = new WebpayNormalService($webpay_config, 'commerce');
    $response = $webpayService->initTransaction($orderId, $amount, Url::fromUri($form['#return_url']), $payment->id());

    $redirect_method = self::REDIRECT_POST;
    $data = [
      'return' => $form['#return_url'],
      'cancel' => $form['#cancel_url'],
      'token_ws' => $response->token,
      'total' => $payment->getAmount()->getNumber(),
    ];

    $form = $this->buildRedirectForm($form, $form_state, $response->url, $data, $redirect_method);

    return $form;
  }

}
