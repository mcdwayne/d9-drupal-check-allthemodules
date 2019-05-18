<?php

namespace Drupal\entity_theme_suggestions\Discovery;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager to add theme suggestions.
 *
 * @package Drupal\entity_theme_suggestions
 */
class EntityThemeSuggestionsPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ThemeSuggestions', $namespaces, $module_handler, 'Drupal\entity_theme_suggestions\Plugin\Definition\EntityThemeSuggestionsInterface', 'Drupal\entity_theme_suggestions\Annotation\EntityThemeSuggestions');
    $this->setCacheBackend($cache_backend, 'entity_theme_suggestions');
  }

  /**
   * Returns the plugin definitions for a given entity.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   The plugin definitions.
   */
  public function getPlugins($entity_type) {
    $suggestions = [];
    if ($entity_type != 'user') {
      return [];
    }
    // Get the alter definitions for the given bundle.
    foreach ($this->getDefinitions() as $id => $definition) {
      if (!empty($definition['entityType']) && $definition['entityType'] == $entity_type) {
        $suggestions[$id] = $definition;
      }
    }
    // Sort the definitions after priority.
    uasort($suggestions, function ($a, $b) {
      if ($a['priority'] == $b['priority']) {
        return 0;
      }

      return ($a['priority'] < $b['priority']) ? -1 : 1;
    });

    // Create the alter plugins.
    foreach ($suggestions as $id => &$alter) {
      $alter = $this->createInstance($id);
    }

    return $suggestions;
  }

}
