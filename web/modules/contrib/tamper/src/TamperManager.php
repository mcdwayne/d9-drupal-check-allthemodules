<?php

namespace Drupal\tamper;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a Tamper plugin manager.
 */
class TamperManager extends DefaultPluginManager implements TamperManagerInterface {

  /**
   * Constructs a TamperManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Tamper',
      $namespaces,
      $module_handler,
      'Drupal\tamper\TamperInterface',
      'Drupal\tamper\Annotation\Tamper'
    );
    $this->alterInfo('tamper_info');
    $this->setCacheBackend($cache_backend, 'tamper_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    // Fetch all categories from definitions and remove duplicates.
    $categories = array_unique(array_values(array_map(function ($definition) {
      return $definition['category'];
    }, $this->getDefinitions())));
    natcasesort($categories);
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedDefinitions(array $definitions = NULL) {
    $definitions = $this->getSortedDefinitions(isset($definitions) ? $definitions : $this->getDefinitions());
    $grouped_definitions = [];
    foreach ($definitions as $id => $definition) {
      $grouped_definitions[(string) $definition['category']][$id] = $definition;
    }
    return $grouped_definitions;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\tamper\TamperInterface[]
   *   List of tamper plugins.
   */
  public function getSortedDefinitions(array $definitions = NULL) {
    // Sort the plugins first by category, then by label.
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();
    uasort($definitions, function ($a, $b) {
      if ($a['category'] != $b['category']) {
        return strnatcasecmp($a['category'], $b['category']);
      }
      return strnatcasecmp($a['label'], $b['label']);
    });
    return $definitions;
  }

}
