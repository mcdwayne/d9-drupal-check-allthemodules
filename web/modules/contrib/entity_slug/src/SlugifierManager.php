<?php

namespace Drupal\entity_slug;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a Slugifier plugin manager.
 */
class SlugifierManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Slugifier',
      $namespaces,
      $module_handler,
      'Drupal\entity_slug\Slugifier\SlugifierInterface',
      'Drupal\entity_slug\Annotation\Slugifier'
    );

    $this->alterInfo('slugifier_info');
    $this->setCacheBackend($cache_backend, 'slugifier_info_plugins');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }
}
