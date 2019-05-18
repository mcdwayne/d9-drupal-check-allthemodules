<?php

namespace Drupal\konamicode\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KonamicodeActionRaptorizeConfiguration.
 */
class KonamicodeActionRaptorizeConfiguration extends KonamicodeActionBaseConfiguration {

  static protected $name = 'Raptorize';
  static protected $machineName = 'raptorize';
  static protected $dependencies = ['konamicode_action_raptorize_jquery_plugin'];

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory, self::$name, self::$machineName, self::$dependencies);
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
      '#markup' => $this->t('An Velociraptor from Jurassic Park will attack the screen when the Konami Code is entered.'),
      '#weight' => -10,
    ];

    $action_delay = $this->getUniqueFieldName('delay');
    $form[parent::getFieldGroupName()][$action_delay] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Delay'),
      '#description' => $this->t('The delay time in milliseconds before the raptor attacks the screen.'),
      '#default_value' => empty($config->get($action_delay)) ? 50 : $config->get($action_delay),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Fetch the unique field names.
    $action_delay = $this->getUniqueFieldName('delay');
    // Save the values.
    $this->configFactory->getEditable('konamicode.configuration')
      ->set($action_delay, $form_state->getValue($action_delay))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
