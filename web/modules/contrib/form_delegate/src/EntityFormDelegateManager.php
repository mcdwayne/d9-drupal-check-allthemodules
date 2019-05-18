<?php

namespace Drupal\form_delegate;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for the entity form delegate plugins.
 *
 * @package Drupal\form_delegate
 */
class EntityFormDelegateManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Form',
      $namespaces,
      $module_handler,
      'Drupal\form_delegate\EntityFormDelegatePluginInterface',
      'Drupal\form_delegate\Annotation\EntityFormDelegate'
    );

    $this->setCacheBackend($cache_backend, 'form_delegates');
  }

  /**
   * Get form alters for the provided bundle.
   *
   * @param string $entity
   *   The entity ID.
   * @param string $bundle
   *   The bundle of the entity.
   * @param string $operation
   *   The form operation.
   * @param string $display
   *   The display ID of the form.
   *
   * @return \Drupal\form_delegate\EntityFormDelegatePluginInterface[]
   *   Form alters for the given entity bundle form display.
   */
  public function getAlters($entity, $bundle, $operation, $display = NULL) {
    $cacheKey = implode(':', func_get_args());

    if (!($bundleFormAlters = $this->cacheGet($cacheKey))) {
      $bundleFormAlters = [];

      // Get the alter definitions for the given bundle.
      foreach ($this->getDefinitions() as $id => $definition) {
        if (is_string($definition['bundle'])) {
          $definition['bundle'] = [$definition['bundle']];
        }
        if ($definition['entity'] != $entity || !in_array($bundle, $definition['bundle'])) {
          continue;
        }

        $displays = isset($definition['display']) ? $definition['display'] : 'default';
        if (is_string($displays) && $displays != '*') {
          $displays = [$displays];
        }

        $operations = $definition['operation'];
        if (is_string($operations) && $operations != '*') {
          $operations = [$operations];
        }

        if (
          ($operations == '*' || in_array($operation, $operations) !== FALSE) &&
          ($displays == '*' || in_array($display, $displays) !== FALSE)
        ) {
          $bundleFormAlters[$id] = $definition;
        }
      }

      // Sort the definitions after priority.
      uasort($bundleFormAlters, function ($a, $b) {
        return $b['priority'] - $a['priority'];
      });

      $this->cacheSet($cacheKey, $bundleFormAlters);
    }
    else {
      // If got from cache we will have other information in STD class. We
      // only need the definition array.
      $bundleFormAlters = $bundleFormAlters->data;
    }

    // Create the alter plugins.
    foreach ($bundleFormAlters as $id => &$alter) {
      $alter = $this->createInstance($id);
    }

    return $bundleFormAlters;
  }

}
