<?php

namespace Drupal\ipsum\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ipsum\Plugin\Type\IpsumPluginManager;
use Drupal\Core\StringTranslation\Translator;

/**
 * Helper class for Ipsum Form generation.
 */
abstract class IpsumBaseForm {

  /**
   * Return form elements the base Ipsum options form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\ipsum\Plugin\Type\IpsumPluginManager
   *   The ipsum plugin manager.
   *
   * @return array
   *   The Form API structure.
   */
  public static function buildForm(ConfigFactoryInterface $config_factory, IpsumPluginManager $ipsum_manager) {
    $config = $config_factory->get('ipsum.settings');

    // Build available provider options.
    $options = array();

    foreach ($ipsum_manager->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['label'];
    }

    // Provider settings.
    $form['provider'] = array(
      '#type' => 'select',
      '#title' => \Drupal::translation()->translate('Provider'),
      '#options' => $options,
      '#default_value' => $config->get('default_provider'),
      '#description' => \Drupal::translation()->translate('Select an ipsum provider.'),
    );

    return $form;
  }

}
