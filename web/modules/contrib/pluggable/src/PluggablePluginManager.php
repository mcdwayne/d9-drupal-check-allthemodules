<?php

namespace Drupal\pluggable;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of plugins.
 *
 * @see plugin_api
 */
abstract class PluggablePluginManager extends DefaultPluginManager {

  /**
   * Plugin type ID.
   * It basically uses to compose other IDs to use them later in cache tags, alters, etc.
   * @var
   */
  protected $pluginTypeId = '';

  /**
   * Plugin type name.
   * @var string
   */
  protected $pluginTypeName = '';

  /**
   * Plugin namespace what is used for plugin discovery.
   * @var
   */
  protected $pluginNamespace = '';

  /**
   * Plugin family interface.
   * @var string
   */
  protected $pluginInterface = '';

  /**
   * Plugin annotation.
   * @var string
   */
  protected $pluginAnnotation = '';

  /**
   * Plugin properties.
   * @var []
   */
  protected $pluginRequiredProperties = [];

  /**
   * Constructs a new manager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct($this->pluginNamespace, $namespaces, $module_handler, $this->pluginInterface, $this->pluginAnnotation);

    $this->alterInfo($this->pluginTypeId . '_info');
    $this->setCacheBackend($cache_backend, $this->pluginTypeId . '_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach ($this->pluginRequiredProperties as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The '.$this->pluginTypeName.' %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
