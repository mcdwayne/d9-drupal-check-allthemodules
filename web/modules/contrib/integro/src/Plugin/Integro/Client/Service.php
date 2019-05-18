<?php

namespace Drupal\integro\Plugin\Integro\Client;

use Drupal\Core\Form\FormStateInterface;
use Drupal\integro\ClientInterface;

/**
 * @IntegroClient(
 *   id = "integro_service",
 *   label = "Service account",
 * )
 */
class Service extends ClientBase implements ClientInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'service_id' => '',
      'service_key' => '',
      'service_json' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['service_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service account ID'),
      '#default_value' => $this->configuration['service_id'],
      '#required' => TRUE,
    ];

    $form['service_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key ID'),
      '#default_value' => $this->configuration['service_key'],
      '#required' => TRUE,
    ];

    $form['service_json'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSON'),
      '#rows' => 5,
      '#default_value' => $this->configuration['service_json'],
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
      $this->configuration['service_id'] = $values['service_id'];
      $this->configuration['service_key'] = $values['service_key'];
      $this->configuration['service_json'] = $values['service_json'];
    }
  }

  public function auth() {
    $auth_result = parent::auth();

    return $auth_result;
  }

}
