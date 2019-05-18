<?php

namespace Drupal\entity_resource_layer;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Plugin manager for the entity resource layer plugins.
 *
 * @package Drupal\entity_resource_layer
 */
class EntityResourceLayerManager extends DefaultPluginManager {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Instantiated plugins.
   *
   * @var array
   */
  protected $instantiated = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct(
      'Plugin/Resource',
      $namespaces,
      $module_handler,
      'Drupal\entity_resource_layer\EntityResourceLayerPluginInterface',
      'Drupal\entity_resource_layer\Annotation\EntityResourceLayer'
    );

    $this->currentRouteMatch = $currentRouteMatch;
    $this->setCacheBackend($cache_backend, 'entity_resource_adaptor');
  }

  /**
   * Get the applicable bundles from definition.
   *
   * @param array $definition
   *   The plugin definition.
   *
   * @return array|string
   *   Array of bundles or a string if all bundles.
   */
  public function getBundleMapFromDefinition(array $definition) {
    if (!isset($definition['bundle'])) {
      return '*';
    }

    $bundle = $definition['bundle'];
    if (is_array($bundle)) {
      return $bundle;
    }
    elseif (is_string($bundle) && $bundle != '*') {
      return [$bundle];
    }

    return $bundle;
  }

  /**
   * Get form alters for the provided bundle.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The bundle of the entity.
   * @param int $apiVersion
   *   (Optional) Get the adaptors of the specified version.
   *
   * @return \Drupal\entity_resource_layer\EntityResourceLayerPluginInterface[]
   *   Form alters for the given bundle.
   */
  public function getAdaptors($entityType, $bundle, $apiVersion) {
    $apiVersion = $apiVersion ?: 1;
    $currentRouteName = $this->currentRouteMatch->getRouteName();
    $routeVersion = $currentRouteName . ':' . $apiVersion;

    // If already discovered and instantiated don't do the logic twice.
    if (
      array_key_exists($entityType, $this->instantiated) &&
      array_key_exists($bundle, $this->instantiated[$entityType]) &&
      array_key_exists($routeVersion, $this->instantiated[$entityType][$bundle])
    ) {
      return $this->instantiated[$entityType][$bundle][$routeVersion];
    }

    $adaptors = [];

    // Get the alter definitions for the given bundle.
    foreach ($this->getDefinitions() as $id => $definition) {
      // Apply entity type filter.
      if ($entityType != $definition['entityType']) {
        continue;
      }

      // Apply bundle filter.
      $bundles = $this->getBundleMapFromDefinition($definition);
      if (is_array($bundles) && array_search($bundle, $bundles) === FALSE) {
        continue;
      }

      // Apply route filter.
      $routes = empty($definition['routes']) ? NULL : (array) $definition['routes'];
      if (!empty($routes) && !$this->routeInArray($currentRouteName, $routes)) {
        continue;
      }

      if ($definition['apiVersion'] != $apiVersion) {
        continue;
      }

      $adaptors[$id] = $definition;
    }

    // Sort the definitions after priority.
    uasort($adaptors, function ($a, $b) {
      return $b['priority'] <=> $a['priority'];
    });

    // Create the adaptor plugins.
    foreach ($adaptors as $id => &$adaptor) {
      $adaptor = $this->createInstance($id);
    }

    $this->instantiated[$entityType][$bundle][$routeVersion] = $adaptors;
    return $adaptors;
  }

  /**
   * Check if a route matches any in given collection.
   *
   * @param string $checkRoute
   *   The route to check against.
   * @param array $routes
   *   The collection to find in.
   *
   * @return bool
   *   Whether it was found.
   */
  protected function routeInArray($checkRoute, array $routes) {
    foreach ($routes as $route) {
      if (
        $route == $checkRoute ||
        ($route{0} == '[' && $route{strlen($route) - 1} == ']' && preg_match($route, $checkRoute))
      ) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
