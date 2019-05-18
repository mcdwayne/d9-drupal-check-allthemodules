<?php

namespace Drupal\commerce_easypaybg\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class EasyPayBgPaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $easypay_config_data = $payment_gateway_plugin->getConfiguration()['easypay_data'];
    $easypay_mode = $easypay_config_data['mode'];
    $config = \Drupal::config('commerce_easypaybg.settings');
    $redirect_method = $config->get('commerce_easypaybg_settings.method');

    $easypay_enc_data = commerce_easypaybg_create_post_data(
      $easypay_config_data[$easypay_mode . '_key'],
      $easypay_config_data[$easypay_mode . '_min'],
      $payment->getOrder(),
      $payment->getAmount()->getNumber(),
      $easypay_config_data['easypay_deadline'],
      $easypay_config_data['easypay_desc_phrase']
    );

    $easypay_form_url = $config->get('commerce_easypaybg_settings.' . $easypay_mode . '_url') .
    '?ENCODED=' . $easypay_enc_data['encoded'] . '&CHECKSUM=' . $easypay_enc_data['checksum'];

    $ch = curl_init($easypay_form_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if(!$curl_error) {
      $output_array = [];
      preg_match("/^(IDN=)(\d{10})$/", $response, $output_array);

      if(isset($output_array[2])) {
        $easypay_code = $output_array[2];
      } else {
        $easypay_code = '';
      }
    } else {
      $easypay_code = '';
    }

    /** Add payment data to custom EasyPayBG db table */
    \Drupal::database()->insert('commerce_easypaybg_payments')
      ->fields([
          'easypay_code' => $easypay_code,
          'commerce_order_id' => $easypay_enc_data['order_id'],
          'status' => 'PENDING',
          'pay_time' => $easypay_enc_data['exp_date'],
          'invoice' => $easypay_enc_data['invoice'],
        ]
      )->execute();

    $redirect_url = Url::fromRoute('commerce_easypaybg.easypaybg_redirect_302',
      [], ['absolute' => TRUE])->toString();

    $data = [
      'return' => $form['#return_url'],
      'cancel' => $form['#cancel_url'],
      'easypay_code' => $easypay_code,
    ];

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }

}
