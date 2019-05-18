<?php

namespace Drupal\kong\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Kong object plugin manager.
 */
class KongObjectManager extends DefaultPluginManager {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct(
      'Plugin/KongObject',
      $namespaces,
      $module_handler,
      'Drupal\kong\Plugin\KongObjectInterface',
      'Drupal\kong\Annotation\KongObject'
    );

    $this->alterInfo('kong_object_info');
    $this->setCacheBackend($cache_backend, 'kong_object_plugins');
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $configuration += $this->configFactory->get('kong.settings')->get();
    return parent::createInstance($plugin_id, $configuration);
  }

}
