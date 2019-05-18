<?php

namespace Drupal\konamicode\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KonamicodeActionRedirectConfiguration.
 */
class KonamicodeActionRedirectConfiguration extends KonamicodeActionBaseConfiguration {

  static protected $name = 'Redirect';
  static protected $machineName = 'redirect';

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory, self::$name, self::$machineName);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load the base main configuration form.
    $form = parent::buildForm($form, $form_state);

    // Fetch the config.
    $config = $this->config('konamicode.configuration');

    $form[parent::getFieldGroupName()][$this->getUniqueFieldName('info')] = [
      '#markup' => $this->t('Redirect users to a given destination URL when the Konami Code is entered.'),
      '#weight' => -10,
    ];

    $action_destination = $this->getUniqueFieldName('destination');
    $form[parent::getFieldGroupName()][$action_destination] = [
      '#type' => 'url',
      '#title' => $this->t('Destination'),
      '#description' => $this->t('This needs to be a complete destination url https://example.com/node/1.'),
      '#default_value' => empty($config->get($action_destination)) ? 'https://youtu.be/dQw4w9WgXcQ' : $config->get($action_destination),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Fetch the unique field names.
    $action_destination = $this->getUniqueFieldName('destination');
    // Save the values.
    $this->configFactory->getEditable('konamicode.configuration')
      ->set($action_destination, $form_state->getValue($action_destination))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
