<?php

namespace Drupal\blockchain\Plugin;

use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class BlockchainAuthManager.
 *
 * @package Drupal\blockchain\Plugin
 */
class BlockchainAuthManager extends DefaultPluginManager {

  const DEFAULT_PLUGIN = 'none';

  /**
   * BlockchainAuthManager constructor.
   *
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {

    parent::__construct('Plugin/BlockchainAuth', $namespaces, $module_handler,
      'Drupal\blockchain\Plugin\BlockchainAuthInterface',
      'Drupal\blockchain\Annotation\BlockchainAuth');
    $this->alterInfo('blockchain_auth_plugin_info');
    $this->setCacheBackend($cache_backend, 'blockchain_auth_plugins');
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
   * Returns auth plugin from config.
   *
   * @param \Drupal\blockchain\Entity\BlockchainConfigInterface $blockchainConfig
   *   Given config.
   *
   * @return BlockchainAuthInterface|null|object
   *   Plugin if any.
   */
  public function getHandler(BlockchainConfigInterface $blockchainConfig) {

    try {
      return $this->createInstance($blockchainConfig->getAuth(), []);
    }
    catch (\Exception $exception) {

      return NULL;
    }
  }

}
