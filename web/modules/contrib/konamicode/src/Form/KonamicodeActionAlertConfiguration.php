<?php

namespace Drupal\konamicode\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KonamicodeActionAlertConfiguration.
 */
class KonamicodeActionAlertConfiguration extends KonamicodeActionBaseConfiguration {

  static protected $name = 'Alert';
  static protected $machineName = 'alert';

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
      '#markup' => $this->t('Will open a "classic" alert window with a predefined message when the Konami Code is entered.'),
      '#weight' => -10,
    ];

    $action_message = $this->getUniqueFieldName('message');
    $form[parent::getFieldGroupName()][$action_message] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#description' => $this->t('The message that needs to be displayed to the user.'),
      '#default_value' => empty($config->get($action_message)) ? 'Konami Code Is Geek' : $config->get($action_message),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Fetch the unique field names.
    $action_message = $this->getUniqueFieldName('message');
    // Save the values.
    $this->configFactory->getEditable('konamicode.configuration')
      ->set($action_message, $form_state->getValue($action_message))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
