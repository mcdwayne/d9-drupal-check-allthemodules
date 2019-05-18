<?php

namespace Drupal\blockchain\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class BlockchainDataManager.
 *
 * @package Drupal\blockchain\Plugin
 */
class BlockchainDataManager extends DefaultPluginManager {

  /**
   * BlockchainDataManager constructor.
   *
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {

    parent::__construct('Plugin/BlockchainData', $namespaces, $module_handler,
      'Drupal\blockchain\Plugin\BlockchainDataInterface',
      'Drupal\blockchain\Annotation\BlockchainData');
    $this->alterInfo('blockchain_data_plugin_info');
    $this->setCacheBackend($cache_backend, 'blockchain_data_plugins');
  }

  /**
   * Manager plugins as list.
   *
   * @return array
   *   Options array.
   */
  public function getList() {

    $list = [];
    foreach ($this->getDefinitions() as $plugin) {
      $list[$plugin['id']] = $plugin['label'];
    }

    return $list;
  }

  /**
   * Extracts plugin id from string data.
   *
   * @param string $data
   *   Given data.
   *
   * @return string|null
   *   Plugin name if any.
   */
  public function extractPluginId($data) {
    if ($parsed = explode('::', $data)) {
      if ($this->hasDefinition($parsed[0])) {
        return $parsed[0];
      }
    }
    return NULL;
  }

  /**
   * Get property of definition.
   *
   * @param string $pluginId
   *   Plugin id.
   * @param string $property
   *   Name of property.
   *
   * @return null|mixed
   *   Property value if any.
   */
  public function definitionGet($pluginId, $property) {
    if ($definition = $this->getDefinition($pluginId, FALSE)) {
      if (isset($definition[$property])) {
        return $definition[$property];
      }
    }
    return NULL;
  }

}
