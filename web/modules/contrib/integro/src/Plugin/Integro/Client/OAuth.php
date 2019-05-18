<?php

namespace Drupal\integro\Plugin\Integro\Client;

use Drupal\Core\Form\FormStateInterface;
use Drupal\integro\ClientInterface;

/**
 * @IntegroClient(
 *   id = "integro_oauth",
 *   label = "OAuth client",
 * )
 */
class OAuth extends ClientBase implements ClientInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'oauth_id' => '',
      'oauth_secret' => '',
      'oauth_json' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['configuration']['oauth_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $this->configuration['oauth_id'],
      '#required' => TRUE,
    ];

    $form['configuration']['oauth_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#default_value' => $this->configuration['oauth_secret'],
      '#required' => TRUE,
    ];

    $form['configuration']['oauth_json'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSON'),
      '#rows' => 5,
      '#default_value' => $this->configuration['oauth_json'],
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
      $this->configuration['oauth_id'] = $values['configuration']['oauth_id'];
      $this->configuration['oauth_secret'] = $values['configuration']['oauth_secret'];
      $this->configuration['oauth_json'] = $values['configuration']['oauth_json'];
    }
  }

}
