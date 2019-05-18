<?php

namespace Drupal\efap;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\efap\Annotation\ExtraField;

/**
 * Class ExtraFieldPluginManager.
 *
 * @package Drupal\efap
 */
class ExtraFieldPluginManager extends DefaultPluginManager {

  /**
   * ExtraFieldPluginManager constructor.
   *
   * @param \Traversable $namespaces
   *   Namespaces.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/ExtraField',
      $namespaces,
      $module_handler,
      ExtraFieldInterface::class,
      ExtraField::class
    );
  }

}
