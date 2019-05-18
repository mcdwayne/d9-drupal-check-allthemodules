<?php

namespace Drupal\gclient\Plugin\Integro\Client;

use Drupal\Core\Form\FormStateInterface;
use Drupal\integro\Plugin\Integro\Client\ServiceBase;
use Google_Client;

abstract class GclientServiceBase extends ServiceBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'project_id' => '',
      'service_json' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['service_json'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSON'),
      '#rows' => 5,
      '#default_value' => $this->configuration['service_json'],
      '#required' => TRUE,
    ];

    $project_options = ['' => $this->t('- Select -')] + \Drupal::service('gclient_google_project.manager')->getOptions();

    $form['project_id'] = [
      '#type' => 'select',
      '#options' => $project_options,
      '#title' => $this->t('Project'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['project_id'] ? $this->configuration['project_id'] : NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['project_id'] = $values['project_id'];
      $this->configuration['service_json'] = $values['service_json'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function auth() {
    $auth_result = parent::auth();

    $directory = 'public://integro/client';
    $filename = $directory . '/' . preg_replace("![^a-z0-9]+!i", "_", $this->configuration['service_id']);
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    file_unmanaged_save_data($this->configuration['service_json'], $filename, FILE_EXISTS_REPLACE);
    $credentials_file = \Drupal::service('file_system')->realpath($filename);
    $client = new Google_Client();
    $client->setAuthConfig($credentials_file);

    $auth_result['client'] = $client;

    return $auth_result;
  }

  /**
   * {@inheritdoc}
   */
  public function authHandle(array $result = []) {
    if (isset($result['access_token']) && $result['access_token'] !== '') {
      $result['authorized'] = TRUE;
    }
    else {
      $result['authorized'] = FALSE;
    }

    if (isset($result['created']) && $result['created'] > 0 && isset($result['expires_in']) && $result['expires_in']) {
      $result['expiration'] = $result['created'] + $result['expires_in'];
    }

    return $result;
  }

}
