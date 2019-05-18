<?php

namespace Drupal\gclient_storage\Plugin\Integro\Client;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gclient\Plugin\Integro\Client\GclientServiceBase;
use Google_Service_Storage;

/**
 * @IntegroClient(
 *   id = "integro_gclient_storage_service",
 *   label = "GClient Storage Service",
 * )
 */
class GclientStorageService extends GclientServiceBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'bucket' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['bucket'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bucket Name'),
      '#default_value' => $this->configuration['bucket'],
      '#required' => TRUE,
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
      $this->configuration['bucket'] = $values['bucket'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function auth() {
    $auth_result = parent::auth();

    $client = $auth_result['client'];
    $client->setScopes([Google_Service_Storage::CLOUD_PLATFORM]);

    $result = $client->fetchAccessTokenWithAssertion();

    $auth_result += $result;

    $auth_result = $this->authHandle($auth_result);

    return $auth_result;
  }

}
