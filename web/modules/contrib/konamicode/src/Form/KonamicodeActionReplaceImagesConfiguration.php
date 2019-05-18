<?php

namespace Drupal\konamicode\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KonamicodeActionReplaceImagesConfiguration.
 */
class KonamicodeActionReplaceImagesConfiguration extends KonamicodeActionBaseConfiguration {

  static protected $name = 'Replace Images';
  static protected $machineName = 'replace_images';

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
      '#markup' => $this->t('Will replace all images on the page with placeholder images from the configured sources when the Konami Code is entered.'),
      '#weight' => -10,
    ];

    $action_integrations = $this->getUniqueFieldName('integrations');
    $form[parent::getFieldGroupName()][$action_integrations] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Integrations'),
      '#description' => $this->t('Select the image sources you want to use. If you select multiple, images will be replaced at random.'),
      '#options' => [
        'baby' => $this->t('Baby'),
        'bacon' => $this->t('Bacon'),
        'bear' => $this->t('Bear'),
        'beer' => $this->t('Beer'),
        'cage' => $this->t('Nicolas Cage'),
        'geese' => $this->t('Geese'),
        'kitten' => $this->t('Kitten'),
        'picsum' => $this->t('Lorem Picsum'),
        'pixel' => $this->t('Lorem Pixel'),
      ],
      '#default_value' => empty($config->get($action_integrations)) ? ['kitten'] : $config->get($action_integrations),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Fetch the unique field names.
    $action_integrations = $this->getUniqueFieldName('integrations');
    // Save the values.
    $this->configFactory->getEditable('konamicode.configuration')
      ->set($action_integrations, $form_state->getValue($action_integrations))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
