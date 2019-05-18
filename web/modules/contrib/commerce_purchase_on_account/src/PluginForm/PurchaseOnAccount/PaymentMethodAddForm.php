<?php

namespace Drupal\commerce_purchase_on_account\PluginForm\PurchaseOnAccount;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PaymentMethodAddForm.
 */
class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->submitBillingProfileForm($form['billing_information'], $form_state);
  }

}
