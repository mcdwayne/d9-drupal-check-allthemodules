<?php

namespace Drupal\alexanders\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Basic settings form for Alexanders.
 *
 * Provides configuration form to toggle fields on product variation types.
 *
 * Class AlexandersManagementForm
 *
 * @package Drupal\alexanders\Form
 */
class AlexandersManagementForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alexanders_management_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['alexanders.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('alexanders.settings');
    $form['apikey'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Server API Keys'),
      '#description' => $this->t('These keys go to Alexanders so they can authenticate with your site.'),
    ];

    $form['apikey']['real'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Real API Key'),
      '#description' => $this->t('Actual API key that will cause the site to process data'),
      '#value' => $config->get('real_api_key') ?? $this->apiKeyGenerator(),
      '#attributes' => $config->get('sandbox_api_key') ? ['readonly' => 'readonly'] : [],
    ];

    $form['apikey']['sandbox'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sandbox API Key'),
      '#description' => $this->t('API key to use during testing & development'),
      '#value' => $config->get('sandbox_api_key') ?? $this->apiKeyGenerator('sandbox'),
      '#attributes' => $config->get('sandbox_api_key') ? ['readonly' => 'readonly'] : [],
    ];

    $form['clientkeys'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Client configuration'),
    ];
    $form['clientkeys']['client_apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client API Key'),
      '#description' => $this->t('API key to connect to the Alexanders API'),
      '#default_value' => $config->get('client_apikey'),
    ];
    $form['clientkeys']['client_enable_sandbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Alexanders Sandbox'),
      '#description' => $this->t('Do not use real API for testing purposes.'),
      '#default_value' => $config->get('client_enable_sandbox'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('alexanders.settings');
    $config->set('real_api_key', $form_state->getValue('real'));
    $config->set('sandbox_api_key', $form_state->getValue('sandbox'));
    $config->set('client_apikey', $form_state->getValue('client_apikey'));
    $config->set('client_enable_sandbox', $form_state->getValue('client_enable_sandbox'));
    $config->save();
  }

  /**
   * Generate API key with given prefix.
   *
   * @param string $prefix
   *   Prefix to append to API key.
   *
   * @return string
   *   Generated API key.
   *
   * @throws \Exception
   */
  private function apiKeyGenerator($prefix = 'alex') {
    $random = new Crypt();
    $str = $prefix . '-' . $random::randomBytesBase64();
    return $str;
  }

}
