<?php

namespace Drupal\pluggable\Plugin\Field\FieldType;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for the pluggable_item field type.
 */
class PluggableItemDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new PluginItemDeriver object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct($base_plugin_id, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $plugin_types = [];

    // Core has no way to list plugin types, so each referenceable plugin type
    // needs to register itself via special hook named pluggable_plugin_info.
    foreach ($this->moduleHandler->getImplementations('pluggable_plugin_info') as $module) {
      $function = $module . '_' . 'pluggable_plugin_info';
      $function($plugin_types);
    }

    foreach ($plugin_types as $plugin_type => $label) {
      $this->derivatives[$plugin_type] = [
        'plugin_type' => $plugin_type,
        'label' => $label,
        'category' => $this->t('Plugin'),
      ] + $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
