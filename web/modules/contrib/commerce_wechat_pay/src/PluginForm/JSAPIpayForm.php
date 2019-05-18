<?php

namespace Drupal\commerce_wechat_pay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class JSAPIpayForm extends PaymentOffsiteForm{
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['openid'] = [
      '#type' => 'textfield',
      '#title' => 'OpenID',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    $openid = $values['openid'];
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    try {
      $payment_gateway_plugin->requestUnifiedOrder($openid, (string) $payment->getOrderId(), $payment->getAmount());
    }
    catch (\Exception $e) {
      \Drupal::logger('commerce_wechat_pay')->error($e->getMessage());
      $form_state->setError($form['openid'], t('Commerce WeChat Pay is having problem to connect to WeChat servers:') . $e->getMessage());
    }
  }

}
