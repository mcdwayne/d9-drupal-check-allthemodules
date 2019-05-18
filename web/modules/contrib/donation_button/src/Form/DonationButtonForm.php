<?php

/**
 * @file
 * Contains \Drupal\donation_button\Form\donation_buttonForm.
 */

namespace Drupal\donation_button\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DonationButtonForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'donation_button_form';
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $config = $this->config('donation_button.settings');    
    $form['#action'] = 'https://www.paypal.com/cgi-bin/webscr';
    $form['cmd'] = array(
      '#type' => 'hidden',
      '#name' => t('cmd'),
      '#value' => '_xclick',
    );
    $form['business'] = array(
      '#type' => 'hidden',
      '#name' => t('business'),
      '#value' => $config->get('paypal_business_account_email'),
    );
    $form['item_name'] = array(
      '#type' => 'hidden',
      '#name' => t('item_name'),
      '#value' => 'donation_button for website',
      '#default_value' => $config->get('paypal_item_name'),
    );
    $form['currency_code'] = array(
      '#type' => 'hidden',
      '#name' => t('currency_code'),
      '#default_value' => $config->get('paypal_currency_code'),
    );
    $form['amount'] = array(
      '#type' => 'hidden',
      '#name' => t('amount'),
      '#default_value' => $config->get('paypal_donation_button_amount'),
    );    
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#default_value' => $config->get('paypal_submit_value'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
     
  }  
}  