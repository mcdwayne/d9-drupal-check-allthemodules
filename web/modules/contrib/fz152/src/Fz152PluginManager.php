<?php

namespace Drupal\fz152;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\fz152\Annotation\Fz152;

/**
 * Class Fz152PluginManager.
 */
class Fz152PluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Fz152', $namespaces, $module_handler, Fz152PluginInterface::class, Fz152::class);

    // hook_fz152_info_alter() — ability to alter plugin values.
    $this->alterInfo('fz152_info');
    $this->setCacheBackend($cache_backend, 'fz152_plugins');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}
