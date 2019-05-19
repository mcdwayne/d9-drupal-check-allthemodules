<?php

namespace Drupal\timed_node_page;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for the time page node plugins.
 *
 * @package Drupal\timed_node_page
 */
class TimedNodePagePluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/TimedNodePage',
      $namespaces,
      $module_handler,
      'Drupal\timed_node_page\TimedNodePagePluginInterface',
      'Drupal\timed_node_page\Annotation\TimedNodePage'
    );

    $this->setCacheBackend($cache_backend, 'timed_node_page');
  }

  /**
   * Gets timed page plugin handlers per path.
   *
   * @return array
   *   Plugin definitions keyed by path.
   */
  public function getAllByPath() {
    $timedPages = [];

    foreach ($this->getDefinitions() as $id => $definition) {
      $timedPages[$definition['path']][$id] = $definition;
    }

    // Sort the definitions after priority.
    foreach ($timedPages as $path => $pages) {
      uasort($pages, function ($a, $b) {
        return $b['priority'] - $a['priority'];
      });

      // Keep only the highest priority.
      $timedPages[$path] = array_shift($pages);
    }

    return $timedPages;
  }

  /**
   * Gets timed page plugin handlers per bundle.
   *
   * @return array
   *   Plugin definitions keyed by bundle.
   */
  public function getApplyingPerBundle() {
    $timedPages = [];

    foreach ($this->getDefinitions() as $id => $definition) {
      $timedPages[$definition['bundle']][$id] = $definition;
    }

    // Sort the definitions after priority.
    foreach ($timedPages as $bundle => $pages) {
      uasort($pages, function ($a, $b) {
        return $b['priority'] - $a['priority'];
      });

      // Keep only the highest priority.
      $timedPages[$bundle] = array_shift($pages);
    }

    return $timedPages;
  }

  /**
   * Gets timed page plugin by given property.
   *
   * @param string $value
   *   What to search for.
   * @param string $property
   *   The property by which to search: bundle / path.
   *
   * @return \Drupal\timed_node_page\TimedNodePagePluginInterface|null
   *   The plugin or NULL if not found.
   */
  public function getBy($value, $property = 'bundle') {
    $cacheKey = 'timed_node:' . implode(':', func_get_args());

    if (!($timedPages = $this->cacheGet($cacheKey))) {
      $timedPages = [];

      // Get the timed node definitions for the given path.
      foreach ($this->getDefinitions() as $id => $definition) {
        if ($value == $definition[$property]) {
          $timedPages[$id] = $definition;
        }
      }

      // Sort the definitions after priority.
      uasort($timedPages, function ($a, $b) {
        return $b['priority'] - $a['priority'];
      });

      $this->cacheSet($cacheKey, $timedPages);
    }
    else {
      // If got from cache we will have other information in STD class. We
      // only need the definition array.
      $timedPages = $timedPages->data;
    }

    if (!count($timedPages)) {
      return NULL;
    }

    reset($timedPages);
    return $this->createInstance(key($timedPages));
  }

}
