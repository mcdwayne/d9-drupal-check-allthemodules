<?php

namespace Drupal\integro\Plugin\Integro\Client;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Rest base class for plugins.
 */
abstract class RestBase extends ClientBase {

  /**
   * {@inheritdoc}
   */
  public function requestPrepare() {
    // To be implemented in descendants.
    // @see $this->clientConfiguration.
  }

  /**
   * {@inheritdoc}
   */
  public function request() {
    // To be implemented in descendants.
    $this->requestPrepare();
    $request_result = [];
    $result = $this->requestHandle($request_result);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function requestHandle($result) {
    // To be implemented in descendants.
    $result_handled = $result;
    return $result_handled;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'rest_protocol' => '',
      'rest_domain' => '',
      'rest_base_path' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Rest protocol.
    $form['rest_protocol'] = [
      '#type' => 'select',
      '#title' => $this->t('Protocol'),
      '#options' => [
        'http' => $this->t('HTTP'),
        'https' => $this->t('HTTPS'),
      ],
      '#empty_option' => $this->t('- Choose the protocol -'),
      '#default_value' => $this->configuration['rest_protocol'],
      '#required' => TRUE,
    ];

    // Domain.
    $form['rest_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $this->configuration['rest_domain'],
      '#required' => TRUE,
    ];

    // Base path.
    $form['rest_base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base path'),
      '#default_value' => $this->configuration['rest_base_path'],
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
      $this->configuration['rest_protocol'] = $values['rest_protocol'];
      $this->configuration['rest_domain'] = $values['rest_domain'];
      $this->configuration['rest_base_path'] = $values['rest_base_path'];
    }
  }

}
