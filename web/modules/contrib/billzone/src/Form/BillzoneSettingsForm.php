<?php

/**
 * @file
 * Contains \Drupal\billzone\Form\BillzoneSettingsForm
 */
 
namespace Drupal\billzone\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\Entity\OrderStatus;

/**
 * Configure billzone settings for this site.
 */
class BillzoneSettingsForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'billzone_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'billzone.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('billzone.settings');
    
    if ( $config->get('mode') == 'sandbox' ) {
      $billzone_base_url = 'https://sandbox.billzone.eu/';
    } else {
      $billzone_base_url = 'https://billzone.eu/';
    }
    
    $url_options = array(
      'attributes' => array(
        'target' => '_blank',
      ),
    );
    
    $form['mode'] = array(
      '#type' => 'radios',
      '#title' => t('Mode'),
      '#default_value' => $config->get('mode'),
      '#options' => array(
        'sandbox' => t("Sandbox"),
        'live' => t("Live"),
      ),
      '#required' => TRUE,
    );
    
    $url = Url::fromUri($billzone_base_url . 'HU/hu/Pages/Company/UnitList.aspx', $url_options);
    $form['default_unit_identifier'] = array(
      '#type' => 'textfield',
      '#title' => t("Default unit identifier"),
      '#default_value' => $config->get('default_unit_identifier'),
      '#required' => TRUE,
      '#description' => \Drupal::l(t('Available unit identifiers'), $url),
    );
    
    $url = Url::fromUri($billzone_base_url . 'HU/hu/Pages/Company/AccountBlockList.aspx', $url_options);
    $form['default_account_block_prefix'] = array(
      '#type' => 'textfield',
      '#title' => t("Default account block prefix"),
      '#default_value' => $config->get('default_account_block_prefix'),
      '#required' => TRUE,
      '#description' => \Drupal::l(t('Available account block prefixes'), $url),
    );
    
    $form['invoice_description'] = array(
      '#type' => 'textarea',
      '#title' => t("Invoice description"),
      '#default_value' => $config->get('invoice_description'),
    );
    
    $form['notes'] = array(
      '#type' => 'textfield',
      '#title' => t("Notes"),
      '#default_value' => $config->get('notes'),
    );
    
    $url = Url::fromUri($billzone_base_url . 'HU/hu/Pages/Company/Policy.aspx', $url_options);
    $form['security_token'] = array(
      '#type' => 'textfield',
      '#title' => t("Security token"),
      '#default_value' => $config->get('security_token'),
      '#required' => TRUE,
      '#description' => \Drupal::l(t('Available security token'), $url),
    );
    
    // Get order statuses
    $statuses = OrderStatus::loadMultiple();
    $order_statuses = array();
    foreach($statuses as $status_key => $status_value) {
      $order_statuses[$status_key] = $status_value->getName();
    }
    
    $form['payment_deadline'] = array(
      '#type' => 'number',
      '#title' => t("Payment deadline"),
      '#default_value' => $config->get('payment_deadline'),
      '#required' => TRUE,
      '#description' => t("How many days have the customer to pay?"),
      '#field_suffix' => t("days"),
    );
    
    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('billzone.settings');
    $config->set('mode', $form_state->getValue('mode'));
    $config->set('default_unit_identifier', $form_state->getValue('default_unit_identifier'));
    $config->set('default_account_block_prefix', $form_state->getValue('default_account_block_prefix'));
    $config->set('inter_eu_vat_exempt', $form_state->getValue('inter_eu_vat_exempt'));
    $config->set('invoice_description', $form_state->getValue('invoice_description'));
    $config->set('notes', $form_state->getValue('notes'));
    $config->set('order_status', $form_state->getValue('order_status'));
    $config->set('security_token', $form_state->getValue('security_token'));
    $config->set('payment_deadline', $form_state->getValue('payment_deadline'));
    $config->save();

    parent::submitForm($form, $form_state);
  }
}