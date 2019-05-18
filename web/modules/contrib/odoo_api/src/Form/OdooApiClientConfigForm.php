<?php

namespace Drupal\odoo_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OdooApiClientConfigForm.
 */
class OdooApiClientConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'odoo_api.api_client',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'odoo_api_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('odoo_api.api_client');

    $form['database'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Odoo Database'),
      '#description' => $this->t('Odoo database name. That is usually a subdomain name.'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $config->get('database'),
      '#weight' => 0,
      '#required' => TRUE,
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Odoo username; typically, this is an email.'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $config->get('username'),
      '#weight' => 1,
      '#required' => TRUE,
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Odoo account password.'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $config->get('password'),
      '#weight' => 2,
      '#required' => TRUE,
    ];
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Odoo instance URL'),
      '#description' => $this->t('Optional instance URL. If not set, will be generated from database name.'),
      '#default_value' => $config->get('url'),
      '#weight' => 3,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('odoo_api.api_client')
      ->set('database', $form_state->getValue('database'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->set('url', $form_state->getValue('url'))
      ->save();
  }

}
