<?php

namespace Drupal\konamicode\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class KonamicodeActionSnowfallConfiguration.
 */
class KonamicodeActionSnowfallConfiguration extends KonamicodeActionBaseConfiguration {

  static protected $name = 'Snowfall';
  static protected $machineName = 'snowfall';
  static protected $dependencies = ['konamicode_action_snowfall_jquery_plugin'];

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
      '#markup' => $this->t('Will make it snow when the Konami Code is entered. <strong>WARNING:</strong> setting extreme parameters might make the browser crash. Make sure to test thoroughly before going public.'),
      '#weight' => -10,
    ];

    $action_flake_count = $this->getUniqueFieldName('flake_count');
    $form[parent::getFieldGroupName()][$action_flake_count] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Flake count'),
      '#description' => $this->t('The amount of flakes to display'),
      '#default_value' => empty($config->get($action_flake_count)) ? 35 : $config->get($action_flake_count),
    ];

    $action_flake_min_size = $this->getUniqueFieldName('flake_min_size');
    $form[parent::getFieldGroupName()][$action_flake_min_size] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Flake Minimum Size'),
      '#default_value' => empty($config->get($action_flake_min_size)) ? 1 : $config->get($action_flake_min_size),
    ];

    $action_flake_max_size = $this->getUniqueFieldName('flake_max_size');
    $form[parent::getFieldGroupName()][$action_flake_max_size] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Flake Maximum Size'),
      '#default_value' => empty($config->get($action_flake_max_size)) ? 2 : $config->get($action_flake_max_size),
    ];

    $action_flake_min_speed = $this->getUniqueFieldName('flake_min_speed');
    $form[parent::getFieldGroupName()][$action_flake_min_speed] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Flake Minimum Speed'),
      '#default_value' => empty($config->get($action_flake_min_speed)) ? 1 : $config->get($action_flake_min_speed),
    ];

    $action_flake_max_speed = $this->getUniqueFieldName('flake_max_speed');
    $form[parent::getFieldGroupName()][$action_flake_max_speed] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Flake Maximum Speed'),
      '#default_value' => empty($config->get($action_flake_max_speed)) ? 5 : $config->get($action_flake_max_speed),
    ];

    $action_flake_round = $this->getUniqueFieldName('flake_round');
    $form[parent::getFieldGroupName()][$action_flake_round] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flake Rounded'),
      '#description' => $this->t('Makes the flakes rounded if the browser supports it.'),
      '#default_value' => is_null($config->get($action_flake_round)) ? TRUE : $config->get($action_flake_round),
    ];

    $action_flake_shadow = $this->getUniqueFieldName('flake_shadow');
    $form[parent::getFieldGroupName()][$action_flake_shadow] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flake Shadow'),
      '#description' => $this->t('Gives the flakes a shadow if the browser supports it.'),
      '#default_value' => is_null($config->get($action_flake_shadow)) ? TRUE : $config->get($action_flake_shadow),
    ];

    // #####################.
    // # ADVANCED SETTINGS #.
    // #####################.
    $form[parent::getFieldGroupName()]['advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced settings'),
      '#description' => t('For more details, please visit the %link. Do not touch those settings unless you know what they are used for.', ['%link' => Link::fromTextAndUrl('documentation page', Url::fromUri('https://www.drupal.org/docs/8/modules/konami-code/action-snowfall'))->toString()]),
      '#open' => FALSE,
    ];

    $action_flake_color = $this->getUniqueFieldName('flake_color');
    $form[parent::getFieldGroupName()]['advanced'][$action_flake_color] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flake Color'),
      '#size' => 7,
      '#maxlength' => 7,
      '#description' => $this->t('An hexadecimal color code starting with "#" in which color to display the flakes.'),
      '#default_value' => empty($config->get($action_flake_color)) ? '#ffffff' : $config->get($action_flake_color),
    ];

    $action_flake_position = $this->getUniqueFieldName('flake_position');
    $form[parent::getFieldGroupName()]['advanced'][$action_flake_position] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flake Position'),
      '#description' => $this->t('The position orientation of the flakes. Refer to the HTML Position element.'),
      '#default_value' => empty($config->get($action_flake_position)) ? 'absolute' : $config->get($action_flake_position),
    ];

    $action_flake_index = $this->getUniqueFieldName('flake_index');
    $form[parent::getFieldGroupName()]['advanced'][$action_flake_index] = [
      '#type' => 'number',
      '#title' => $this->t('Flake Index'),
      '#description' => $this->t('The Z-Index of the flakes.'),
      '#default_value' => empty($config->get($action_flake_index)) ? 99999 : $config->get($action_flake_index),
    ];

    $action_flake_collection = $this->getUniqueFieldName('flake_collection');
    $form[parent::getFieldGroupName()]['advanced'][$action_flake_collection] = [
      '#type' => 'textarea',
      '#title' => $this->t('Flake Collection'),
      '#description' => $this->t('Any valid jQuery selector. With this field you can specify an element on which snow will collect and pile up.'),
      '#default_value' => empty($config->get($action_flake_collection)) ? '' : $config->get($action_flake_collection),
    ];

    $action_flake_collection_height = $this->getUniqueFieldName('flake_collection_height');
    $form[parent::getFieldGroupName()]['advanced'][$action_flake_collection_height] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Flake Collection height'),
      '#default_value' => empty($config->get($action_flake_collection_height)) ? 40 : $config->get($action_flake_collection_height),
    ];

    $action_flake_device_orientation = $this->getUniqueFieldName('flake_device_orientation');
    $form[parent::getFieldGroupName()]['advanced'][$action_flake_device_orientation] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flake Device Orientation'),
      '#description' => $this->t('Flake Device Orientation support.'),
      '#default_value' => is_null($config->get($action_flake_device_orientation)) ? FALSE : $config->get($action_flake_device_orientation),
    ];

    $action_flake_use_image = $this->getUniqueFieldName('flake_use_image');
    $form[parent::getFieldGroupName()]['advanced'][$action_flake_use_image] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Flake Use Image'),
      '#description' => $this->t("Use an image instead of css elements."),
      '#default_value' => is_null($config->get($action_flake_use_image)) ? FALSE : $config->get($action_flake_use_image),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $action_flake_color = $this->getUniqueFieldName('flake_color');
    // Validate the Key Code Sequence.
    if (!$this->validateFlakeColor($form_state->getValue($action_flake_color))) {
      $form_state->setErrorByName($action_flake_color, $this->t('There seems to be an error with your Flake Color.'));
    }
  }

  /**
   * Function that will validate the Flake Color.
   *
   * @param string $color
   *   The color entered in the form.
   *
   * @return bool
   *   Returns the result of the validation.
   */
  public function validateFlakeColor($color) {
    return (bool) preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Fetch the unique field names.
    $action_flake_count = $this->getUniqueFieldName('flake_count');
    $action_flake_color = $this->getUniqueFieldName('flake_color');
    $action_flake_position = $this->getUniqueFieldName('flake_position');
    $action_flake_index = $this->getUniqueFieldName('flake_index');
    $action_flake_min_size = $this->getUniqueFieldName('flake_min_size');
    $action_flake_max_size = $this->getUniqueFieldName('flake_max_size');
    $action_flake_min_speed = $this->getUniqueFieldName('flake_min_speed');
    $action_flake_max_speed = $this->getUniqueFieldName('flake_max_speed');
    $action_flake_round = $this->getUniqueFieldName('flake_round');
    $action_flake_shadow = $this->getUniqueFieldName('flake_shadow');
    $action_flake_collection = $this->getUniqueFieldName('flake_collection');
    $action_flake_collection_height = $this->getUniqueFieldName('flake_collection_height');
    $action_flake_device_orientation = $this->getUniqueFieldName('flake_device_orientation');
    $action_flake_use_image = $this->getUniqueFieldName('flake_use_image');

    // Save the values.
    $this->configFactory->getEditable('konamicode.configuration')
      ->set($action_flake_count, $form_state->getValue($action_flake_count))
      ->set($action_flake_color, $form_state->getValue($action_flake_color))
      ->set($action_flake_position, $form_state->getValue($action_flake_position))
      ->set($action_flake_index, $form_state->getValue($action_flake_index))
      ->set($action_flake_min_size, $form_state->getValue($action_flake_min_size))
      ->set($action_flake_max_size, $form_state->getValue($action_flake_max_size))
      ->set($action_flake_min_speed, $form_state->getValue($action_flake_min_speed))
      ->set($action_flake_max_speed, $form_state->getValue($action_flake_max_speed))
      ->set($action_flake_round, $form_state->getValue($action_flake_round))
      ->set($action_flake_shadow, $form_state->getValue($action_flake_shadow))
      ->set($action_flake_collection, $form_state->getValue($action_flake_collection))
      ->set($action_flake_collection_height, $form_state->getValue($action_flake_collection_height))
      ->set($action_flake_device_orientation, $form_state->getValue($action_flake_device_orientation))
      ->set($action_flake_use_image, $form_state->getValue($action_flake_use_image))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
