<?php

namespace Drupal\mask\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin manager for field widgets supported by Mask.
 */
class FieldWidgetPluginManager extends DefaultPluginManager {

  /**
   * A set of defaults to be referenced by $this->processDefinition().
   *
   * @var array
   */
  protected $defaults = [
    'element_parents' => ['value'],
    'defaults' => [
      'value' => '',
      'reverse' => FALSE,
      'clearifnotmatch' => FALSE,
      'selectonfocus' => FALSE,
    ],
    'class' => 'Drupal\\mask\\Plugin\\FieldWidgetPlugin',
  ];

  /**
   * Constructs a new \Drupal\mask\Plugin\FieldWidgetPluginManager object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'mask_field_widgets');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      // Uses YAML discovery.
      $discovery = new YamlDiscovery('mask_field_widgets', $this->moduleHandler->getModuleDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

}
