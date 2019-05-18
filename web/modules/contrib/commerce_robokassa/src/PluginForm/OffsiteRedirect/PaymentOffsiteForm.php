<?php

namespace Drupal\commerce_robokassa\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\Price;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment->save();

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $data = [];

    $payment_gateway_configuration = $payment_gateway_plugin->getConfiguration();
    $user_name = $payment_gateway_configuration["MrchLogin"];
    $password = $payment_gateway_configuration['pass1'];
    $double_staged = !$form['#capture'];
    $mode = $payment->getPaymentGatewayMode() == 'live' ? false : true;

    $logging = $payment_gateway_configuration['logging'] == 0 ? false : true;
    $timeout = empty($payment_gateway_configuration['timeout']) ? null : $payment_gateway_configuration['timeout'];
    $redirect_url = $payment_gateway_configuration['server_url_' . $payment->getPaymentGatewayMode()];
    $form['#action'] = $redirect_url;
    $data["MerchantLogin"] = $payment_gateway_configuration['MrchLogin'];
    $amount = $payment->getOrder()->getTotalPrice();
    $data["OutSum"] = number_format($amount->getNumber(), 2, '.', '');
    $data["InvId"] = $payment->getOrderId();
    $data["shp_trx_id"] = $payment->id();
    // For test transactions.
    if ($payment->getPaymentGatewayMode() == 'test') {
      $data['IsTest'] = '1';
    }

    $signature_data = array(
      $data["MerchantLogin"],
      $data["OutSum"],
      $data["InvId"],
      $payment_gateway_configuration['pass1'],
      'shp_trx_id=' . $data["shp_trx_id"],
    );

    // Calculate signature.
    $data['SignatureValue'] = hash($payment_gateway_configuration['hash_type'], implode(':', $signature_data));

    $inv_desc_params = array(
      '@order_id' => $payment->getOrderId(),
      '@mail' => $payment->getOrder()->getEmail(),
    );

    $inv_desc = t('Order ID: @order_id, User mail: @mail', $inv_desc_params);
    $data['InvDesc'] = Unicode::truncate($inv_desc, 100);

    if (isset($payment->getOrder()->getData('commerce_robokassa')['IncCurrLabel'])) {
      $data['IncCurrLabel'] = $payment->getOrder()->getData('commerce_robokassa')['IncCurrLabel'];
    }

    $payment->save();


    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, self::REDIRECT_POST);
  }

}
