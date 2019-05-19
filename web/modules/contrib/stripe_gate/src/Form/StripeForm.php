<?php

namespace Drupal\stripe\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * StripeForm class.
 */
class StripeForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stripe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Default settings.
    $config = $this->config('stripe.settings');

    $form['stripe_test'] = array(
      '#type' => 'details',
      '#title' => t('Test keys'),
      '#open' => TRUE,
    );

    $form['stripe_test']['pk_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publishable key'),
      '#default_value' => $config->get('stripe.pk_test'),
      '#description' => $this->t('Find the key here: https://dashboard.stripe.com/account/apikeys.'),
    ];

    $form['stripe_test']['sk_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#default_value' => $config->get('stripe.sk_test'),
      '#description' => $this->t('Find the key here: https://dashboard.stripe.com/account/apikeys.'),
    ];

    $form['stripe_live'] = array(
      '#type' => 'details',
      '#title' => t('Live keys'),
      '#open' => TRUE,
    );

    $form['stripe_live']['pk_live'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publishable key'),
      '#default_value' => $config->get('stripe.pk_live'),
      '#description' => $this->t('Find the key here: https://dashboard.stripe.com/account/apikeys.'),
    ];

    $form['stripe_live']['sk_live'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#default_value' => $config->get('stripe.sk_live'),
      '#description' => $this->t('Find the key here: https://dashboard.stripe.com/account/apikeys.'),
    ];

    $form['use_test'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use test'),
      '#default_value' => $config->get('stripe.use_test'),
      '#description' => $this->t('If you uncheck this, the app will use the live data'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('stripe.settings')
      ->set('stripe.pk_test', $form_state->getValue('pk_test'))
      ->set('stripe.sk_test', $form_state->getValue('sk_test'))
      ->set('stripe.pk_live', $form_state->getValue('pk_live'))
      ->set('stripe.sk_live', $form_state->getValue('sk_live'))
      ->set('stripe.use_test', $form_state->getValue('use_test'))
      ->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // This function returns the name of the settings files we will
    // create / use.
    return [
      'stripe.settings',
    ];
  }

}
