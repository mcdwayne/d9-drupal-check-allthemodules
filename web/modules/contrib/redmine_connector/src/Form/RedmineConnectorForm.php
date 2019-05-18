<?php

namespace Drupal\redmine_connector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RedmineConnectorForm.
 */
class RedmineConnectorForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'redmine_connector.redmine_connector_config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redmine_connector_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('redmine_connector.redmine_connector_config');
    $form['redmine_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redmine base URL'),
      '#description' => $this->t('Without trailing slash'),
      '#default_value' => $config->get('redmine_url'),
      '#required' => TRUE,
    ];
    $form['redmine_login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redmine login'),
      '#description' => $this->t('Login for admin user in your Redmine.'),
      '#default_value' => $config->get('redmine_login'),
      '#required' => TRUE,
    ];
    $form['redmine_pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Redmine password'),
      '#description' => $this->t('Password for admin user in your Redmine.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('redmine_connector.redmine_connector_config')
      ->set('redmine_url', $form_state->getValue('redmine_url'))
      ->set('redmine_login', $form_state->getValue('redmine_login'))
      ->set('redmine_pass', $form_state->getValue('redmine_pass'))
      ->save();
  }

}
