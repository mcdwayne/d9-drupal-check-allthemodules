<?php

/**
 * @file
 * Contains Drupal\donation_button\donation_buttonConfigurationForm;
 */
namespace Drupal\donation_button\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class donation_buttonConfigurationForm.
 * @package Drupal\donation_button\Form.
 */
Class DonationButtonConfigurationForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'donation_button.settings',
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'donation_button_config_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $config = $this->config('donation_button.settings');
    $form['donation_button'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('donation_button Settings'),
    ];
    $form['donation_button']['paypal_business_account_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Receiver PayPal account'),
      '#description' => $this->t("The PayPal account's e-mail address."),
      '#required' => TRUE,
      '#default_value' => $config->get('paypal_business_account_email'),
    ];
    $form['donation_button']['paypal_item_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PayPal item name'),
      '#description' => $this->t('The visible item name at PayPal.'),
      '#required' => TRUE,
      '#default_value' => $config->get('paypal_item_name'),
    ];
    $form['donation_button']['paypal_donation_button_amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PayPal donation_button amount'),
      '#description' => $this->t('The donation_button amount value.'),
      '#required' => TRUE,
      '#default_value' => $config->get('paypal_donation_button_amount'),
    ];
    $form['donation_button']['paypal_currency_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PayPal currency code'),
      '#description' => $this->t('ISO 4217 Currency Code.'),
      '#required' => TRUE,
      '#default_value' => $config->get('paypal_currency_code'),
    ];
    $form['donation_button']['paypal_submit_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form submit text'),
      '#description' => $this->t('The button text when someone sends the form.'),
      '#required' => TRUE,
      '#default_value' => $config->get('paypal_submit_value'),
    ];
    return parent::buildForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $paypal_business_email = $form_state->getValues('paypal_business_account_email');
    $paypal_item_name = $form_state->getValues('paypal_item_name');
    $paypal_donation_button_amount = $form_state->getValues('paypal_donation_button_amount');
    $paypal_currency_code = $form_state->getValues('paypal_currency_code');
    $paypal_submit_value = $form_state->getValues('paypal_submit_value');
    
    $config = $this->config('donation_button.settings')
      ->set('paypal_business_account_email', $paypal_business_email['paypal_business_account_email'])
      ->set('paypal_item_name', $paypal_item_name['paypal_item_name'])
      ->set('paypal_donation_button_amount', $paypal_donation_button_amount['paypal_donation_button_amount'])
      ->set('paypal_currency_code', $paypal_currency_code['paypal_currency_code'])
      ->set('paypal_submit_value', $paypal_submit_value['paypal_submit_value'])
      ->save();    
    parent::submitForm($form, $form_state);
  }
}
