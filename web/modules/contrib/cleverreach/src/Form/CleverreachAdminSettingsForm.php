<?php

namespace Drupal\cleverreach\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CleverreachAdminSettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'cleverreach_admin_settings';
  }

  protected function getEditableConfigNames() {
    return [
      'cleverreach.settings',
    ];
  }

 function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cleverreach.settings');

    $form['api_details'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('CleverReach API Details'),
      '#weight' => 1,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['api_details']['cleverreach_api_key'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('api_key'),
      '#title' => $this->t('Your CleverReach API-Key'),
      '#description' => $this->t('Enter your personal CleverReach API-Key.'),
    );
    $form['api_details']['cleverreach_wsdl_url'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('wsdl_url'),
      '#title' => $this->t('CleverReach WSDL-URL'),
      '#description' => $this->t('Enter the CleverReach WSDL-URL.'),
    );
    $form['grp_details'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('CleverReach Group Sync'),
      '#weight' => 2,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $timestamp = \Drupal::state()->get('cleverreach.last_group_fetch');
    $last_update = !empty($timestamp) ? date("Y-m-d H:i:s", $timestamp) : t('never');
    $form['grp_details']['last_update'] = array(
      '#markup' => $this->t('Last group update: @date', array('@date' => $last_update)) . "<br />",
    );
    $form['grp_details']['update_button'] = array(
      '#type' => 'button',
      '#value' => $this->t('Fetch groups now'),
      '#limit_validation_errors' => array(),
      '#executes_submit_callback' => TRUE,
      '#submit' => array('cleverreach_group_update'),
    ); 

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('cleverreach.settings');
    $config->set('api_key', $form_state->getValue('cleverreach_api_key'));
    $config->set('wsdl_url', $form_state->getValue('cleverreach_wsdl_url'));
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
