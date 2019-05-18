<?php

namespace Drupal\konamicode\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KonamicodeActionImageSpawnConfiguration.
 */
class KonamicodeActionImageSpawnConfiguration extends KonamicodeActionBaseConfiguration {

  static protected $name = 'Image Spawn';
  static protected $machineName = 'image_spawn';
  static protected $dependencies = ['konamicode_action_image_spawn_jquery_plugin'];

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
      '#markup' => $this->t('Will spawn a given amount of images on the screen when the Konami Code is entered.'),
      '#weight' => -10,
    ];

    // TODO: Validate internal & external URLs. See:
    // https://www.drupal.org/project/eu_cookie_compliance/issues/2798829 and
    // https://cgit.drupalcode.org/eu-cookie-compliance/tree/src/Form/EuCookieComplianceConfigForm.php.
    $action_images = $this->getUniqueFieldName('images');
    $form[parent::getFieldGroupName()][$action_images] = [
      '#type' => 'textarea',
      '#title' => $this->t('Images'),
      '#description' => $this->t('The absolute URL of the images to attack the user with. Each different image should be separated by a line break.'),
      '#default_value' => empty($config->get($action_images)) ? '/libraries/image_spawn/assets/images/druplicon-small.png' : $config->get($action_images),
    ];

    $action_amount = $this->getUniqueFieldName('amount');
    $form[parent::getFieldGroupName()][$action_amount] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 5000,
      '#title' => $this->t('Amount of images'),
      '#description' => $this->t('Number of images to appear on the screen.'),
      '#default_value' => empty($config->get($action_amount)) ? 500 : $config->get($action_amount),
    ];

    $action_delay = $this->getUniqueFieldName('delay');
    $form[parent::getFieldGroupName()][$action_delay] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 200,
      '#title' => $this->t('Spawn delay'),
      '#description' => $this->t('The delay in milliseconds for each of the images to be spawn.'),
      '#default_value' => empty($config->get($action_delay)) ? 10 : $config->get($action_delay),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Fetch the unique field names.
    $action_images = $this->getUniqueFieldName('images');
    $action_amount = $this->getUniqueFieldName('amount');
    $action_delay = $this->getUniqueFieldName('delay');
    // Save the values.
    $this->configFactory->getEditable('konamicode.configuration')
      ->set($action_images, $form_state->getValue($action_images))
      ->set($action_amount, $form_state->getValue($action_amount))
      ->set($action_delay, $form_state->getValue($action_delay))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
