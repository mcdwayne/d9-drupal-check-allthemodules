<?php

namespace Drupal\commerce_rbspayment\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\Price;
use Drupal\commerce_rbspayment\CommerceRbsPaymentApi;
use Drupal\Core\Form\FormStateInterface;

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

    $data = [];

    $payment_gateway_configuration = $payment_gateway_plugin->getConfiguration();
    $user_name = $payment_gateway_configuration['username'];
    $password = $payment_gateway_configuration['password'];
    $double_staged = !$form['#capture'];
    $mode = $payment_gateway_configuration['mode'] == 'live' ? false : true;
    $logging = $payment_gateway_configuration['logging'] == 0 ? false : true;
    $timeout = $payment_gateway_configuration['timeout'];
    $url = $payment_gateway_configuration['server_url'];
    $test_url = $payment_gateway_configuration['server_test_url'];

    $rbs = new CommerceRbsPaymentApi($url, $test_url, $user_name, $password, $timeout, $double_staged, $mode, $logging);

    $amount = $payment->getOrder()->getTotalPrice();
    $payment->save();
    $response = $rbs->registerOrder(
      $payment->id(),
      $this->toMinorUnits($amount),
      $this->getCurrencyNumericCode($amount->getCurrencyCode()),
      $form["#return_url"],
      $form['#cancel_url'],
      '',
      'RU'
    );
    if (!isset($response['errorCode'])){
      $redirect_url = $response['formUrl'] ;
      $payment->setRemoteId($response['orderId']);
      $payment->save();
    } else {
      drupal_set_message($this->t('Error # %code: %message', [
        '%code' => $response['errorCode'],
        '%message' => $response['errorMessage']]), 'error');
      $redirect_url = $form['#cancel_url'] ;
    }

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, self::REDIRECT_GET);
  }

  /**
   * Converts the given amount to its minor units.
   *
   * For example, 9.99 USD becomes 999.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The amount.
   *
   * @return int
   *   The amount in minor units, as an integer.
   */
  protected function toMinorUnits(Price $amount) {
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = Currency::load($amount->getCurrencyCode());
    $fraction_digits = $currency->getFractionDigits();
    $number = $amount->getNumber();
    if ($fraction_digits > 0) {
      $number = Calculator::multiply($number, pow(10, $fraction_digits));
    }

    return round($number, 0);
  }

  /**
   * @param string $currency_code
   *
   * @return string
   */
  function getCurrencyNumericCode($currency_code) {
    $currency = Currency::load($currency_code);
    return $currency->getNumericCode();
  }

}
