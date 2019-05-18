<?php

namespace Drupal\cloudfront_edge_caching\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for Cloudfront credentials.
 */
class CecAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cec_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cec.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('cec.settings');

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    // Region.
    $form['settings']['cec_region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Region'),
      '#default_value' => $config->get('cec_region'),
      '#size' => 10,
      '#maxlength' => 128,
      '#description' => $this->t('Ej: us-east-1'),
      '#required' => TRUE,
    ];

    // Key.
    $form['settings']['cec_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Key Id'),
      '#default_value' => $config->get('cec_key'),
      '#size' => 20,
      '#maxlength' => 128,
      '#description' => $this->t('Ej: EOjWGh6Keft9czeNkmHsa1aMcrhYukxdlIXRayDt'),
      '#required' => TRUE,
    ];

    // Secret.
    $form['settings']['cec_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Access Key'),
      '#default_value' => $config->get('cec_secret'),
      '#size' => 50,
      '#maxlength' => 128,
      '#description' => $this->t('Ej: AHIAJF6JNSRJRVNSDOKA'),
      '#required' => TRUE,
    ];

    // Distribution ID.
    $form['settings']['cec_distribution_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Distribution ID'),
      '#default_value' => $config->get('cec_distribution_id'),
      '#size' => 20,
      '#maxlength' => 128,
      '#description' => $this->t('Ej: E206SWIPUZ2Z48'),
      '#required' => TRUE,
    ];

    $form['cache'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cache configuration'),
    ];

    // Auto clear cache for content
    // TODO: Pending to https://www.drupal.org/node/2712079
    $cec_auto_cache_clear_content = [
      '#type' => 'checkboxes',
      '#options' => ['cec_auto_cache' => $this->t('Clear cache when update content')],
    ];

    if ($config->get('cec_auto_cache_clear_content')) {
      $cec_auto_cache_clear_content['#default_value'] = $config->get('cec_auto_cache_clear_content');
    }

    $form['cache']['cec_auto_cache_clear_content'] = $cec_auto_cache_clear_content;

    // Auto clear cache for users
    // TODO: Pending to https://www.drupal.org/node/2712079
    $cec_auto_cache_clear_users = [
      '#type' => 'checkboxes',
      '#options' => ['cec_auto_cache' => $this->t('Clear cache when update users')],
    ];

    if ($config->get('cec_auto_cache_clear_users')) {
      $cec_auto_cache_clear_users['#default_value'] = $config->get('cec_auto_cache_clear_users');
    }
    $form['cache']['cec_auto_cache_clear_users'] = $cec_auto_cache_clear_users;

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Get data.
    $data = $form_state->getValues();

    // Test connection.
    $test_connection_cec = cloudfront_edge_caching_test_connection($data['cec_region'], $data['cec_key'], $data['cec_secret']);

    if ($test_connection_cec[0] == FALSE) {
      switch ($test_connection_cec[1]) {
        case '403':
          $form_state->setErrorByName('cec_key', $this->t('The credentials are incorrect.'));
          $form_state->setErrorByName('cec_secret', $this->t('The credentials are incorrect.'));
          break;

        default:
          $form_state->setErrorByName('', $this->t($test_connection_cec[2]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config('cec.settings')
      // Set the submitted configuration setting.
      ->set('cec_region', $form_state->getValue('cec_region'))
      ->set('cec_key', $form_state->getValue('cec_key'))
      ->set('cec_secret', $form_state->getValue('cec_secret'))
      ->set('cec_distribution_id', $form_state->getValue('cec_distribution_id'))
      ->set('cec_auto_cache_clear_content', $form_state->getValue('cec_auto_cache_clear_content'))
      ->set('cec_auto_cache_clear_users', $form_state->getValue('cec_auto_cache_clear_users'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
