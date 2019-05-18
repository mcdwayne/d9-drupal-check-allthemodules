<?php

namespace Drupal\konamicode\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KonamicodeActionAsteroidsConfiguration.
 */
class KonamicodeActionAsteroidsConfiguration extends KonamicodeActionBaseConfiguration {

  static protected $name = 'Asteroids';
  static protected $machineName = 'asteroids';
  static protected $dependencies = ['konamicode_action_asteroids_jquery_plugin'];

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

    $form[parent::getFieldGroupName()][$this->getUniqueFieldName('info')] = [
      '#markup' => $this->t('Will allow you to destroy the page "asteroids" style when the Konami Code is entered.'),
      '#weight' => -10,
    ];

    return $form;
  }

}
