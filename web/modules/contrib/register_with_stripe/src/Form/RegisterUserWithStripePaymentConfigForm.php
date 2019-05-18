<?php

/**
 * @file
 * Contains \Drupal\register_user_with_stripe_payment\Form\RegisterUserWithStripePaymentConfigForm.
 */

namespace Drupal\register_user_with_stripe_payment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Controller for the admin making a stripe Payment configuration form.
 */
class RegisterUserWithStripePaymentConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_user_with_stripe_payment_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('register_user_with_stripe_payment.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['register_user_with_stripe_payment.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['register_user_with_stripe_payment_api_secret_key'] = [
      '#type' => 'textfield',
      '#title' => 'Secret Key',
      '#default_value' => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_secret_key'),
      '#description' => '<p>' . t('Enter your secret key e.g sk_test_BQokikJOvBiI2HlWgH4olfQ2') . '</p>',
      '#required' => TRUE,
    ];
    $form['register_user_with_stripe_payment_api_publishable_key'] = [
      '#type' => 'textfield',
      '#title' => 'Publishable Key',
      '#default_value' => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_api_publishable_key'),
      '#description' => '<p>' . t('Enter your api key e.g pk_test_6pRNASCoBOKtIshFeQd4XMUh') . '</p>',
      '#required' => TRUE,
    ];
    $form['register_user_with_stripe_payment_customer_email'] = [
      '#type' => 'textfield',
      '#title' => 'Customer Email',
      '#default_value' => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_customer_email'),
      '#description' => '<p>' . t('Enter your customer email id') . '</p>',
      '#required' => TRUE,
    ];
    $form['register_user_with_stripe_payment_registration_amount'] = [
      '#type' => 'textfield',
      '#title' => 'Registration Amount',
      '#default_value' => \Drupal::config('register_user_with_stripe_payment.settings')->get('register_user_with_stripe_payment_registration_amount'),
      '#description' => '<p>' . t('Enter registration amount e.g 10 or 10.50') . '</p>',
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

}