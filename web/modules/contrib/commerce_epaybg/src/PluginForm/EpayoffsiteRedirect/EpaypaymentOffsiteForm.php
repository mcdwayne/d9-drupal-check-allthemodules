<?php

namespace Drupal\commerce_epaybg\PluginForm\EpayoffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class EpaypaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $epay_config_data = $payment_gateway_plugin->getConfiguration()['epay_data'];
    $epay_mode = $epay_config_data['mode'];
    $config = \Drupal::config('commerce_epaybg.settings');

    $redirect_url = $config->get('commerce_epaybg_settings.' . $epay_mode . '_url');
    $redirect_method = $config->get('commerce_epaybg_settings.method');
    $epay_enc_data = commerce_epaybg_create_post_data($epay_config_data[$epay_mode . '_key'],
                        $epay_config_data[$epay_mode . '_min'],
                        $payment->getOrder(),
                        $payment->getAmount()->getNumber(),
                        $epay_config_data['epay_desc_phrase']
                      );

    /** Add payment data to custom EpayBG db table */
    \Drupal::database()->insert('commerce_epaybg_payments')
    ->fields([
        'invoice' => $epay_enc_data['invoice'],
        'commerce_order_id' => $epay_enc_data['order_id'],
        'epay_payment_total_price' => $payment->getAmount()->getNumber(),
      ]
      )->execute();

    $data = [
      'PAGE' => 'paylogin',
      'ENCODED' => $epay_enc_data['encoded'],
      'CHECKSUM' => $epay_enc_data['checksum'],
      'URL_OK' => $form['#return_url'],
      'URL_CANCEL' => $form['#cancel_url'],
    ];

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }

}
