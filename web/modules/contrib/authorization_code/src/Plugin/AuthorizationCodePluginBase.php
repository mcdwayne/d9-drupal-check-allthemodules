<?php

namespace Drupal\authorization_code\Plugin;

use Drupal\authorization_code\ConfigurablePluginTrait;
use Drupal\authorization_code\PluginFormTrait;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Base class for all of authorization_code plugins.
 */
abstract class AuthorizationCodePluginBase extends PluginBase implements PluginInspectionInterface, PluginFormInterface, ConfigurablePluginInterface {

  use ConfigurablePluginTrait;
  use PluginFormTrait;

  /**
   * Sms constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'plugin_id' => $this->getPluginId(),
      'settings' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration([
      'plugin_id' => $this->getPluginId(),
      'settings' => $form_state->getValues(),
    ]);
  }

  /**
   * Is this a broken plugin?
   *
   * @return bool
   *   Is this a broken plugin?
   */
  public function isBroken(): bool {
    return FALSE;
  }

}
