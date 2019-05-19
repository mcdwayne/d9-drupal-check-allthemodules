<?php

/**
 * @file
 * Contains \Drupal\social_counters\SocialCountersManager.
 */

namespace Drupal\social_counters;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages social counters plugins.
 */
class SocialCountersManager extends DefaultPluginManager {
  /**
   * Creates the discovery object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/SocialCounters';
    $plugin_interface = 'Drupal\social_counters\SocialCountersInterface';
    $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin';

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);
    $this->alterInfo('social_counters');
    $this->setCacheBackend($cache_backend, 'social_counters_info');
  }
}
