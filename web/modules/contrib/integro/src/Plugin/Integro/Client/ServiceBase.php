<?php

namespace Drupal\integro\Plugin\Integro\Client;

use Drupal\Core\Form\FormStateInterface;
use Drupal\integro\ClientInterface;

class ServiceBase extends ClientBase implements ClientInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'service_id' => '',
      'service_key' => '',
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
    }
  }

  /**
   * {@inheritdoc}
   */
  public function auth() {
    $auth_result = parent::auth();

    return $auth_result;
  }

}
