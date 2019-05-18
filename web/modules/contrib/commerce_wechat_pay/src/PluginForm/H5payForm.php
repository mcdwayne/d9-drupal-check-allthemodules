<?php

namespace Drupal\commerce_wechat_pay\PluginForm;


use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class H5payForm extends PaymentOffsiteForm {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    try {
      /** @var \Drupal\commerce_payment\Entity\Payment $payment_entity */
      $payment_entity = $payment_gateway_plugin->requestH5UnifiedOrder((string) $payment->getOrderId(), $payment->getAmount());
    }
    catch (\Exception $e) {
      \Drupal::logger('commerce_wechat_pay_form')->error($e->getMessage());
    }

    return $form;
  }

}
