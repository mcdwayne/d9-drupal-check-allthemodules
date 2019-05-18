<?php

namespace Drupal\healthcheck\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the Healthcheck plugin plugin manager.
 */
class HealthcheckPluginManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * Constructs a new HealthcheckPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {

    parent::__construct('Plugin/Healthcheck', $namespaces, $module_handler, 'Drupal\healthcheck\Plugin\HealthcheckPluginInterface', 'Drupal\healthcheck\Annotation\Healthcheck');

    $this->alterInfo('healthcheck_healthcheck_plugin_info');
    $this->setCacheBackend($cache_backend, 'healthcheck_healthcheck_plugin_plugins');
  }

  /**
   * Gets healthcheck plugin tags as an array.
   *
   * @return array
   *   An array of healthcheck plugin tags.
   */
  public function getTags() {
    $tags = [];

    // Get all the healthcheck plugin definitions.
    $defs = $this->getDefinitions();

    // Go through each, building the array.
    foreach ($defs as $def) {
      foreach ($def['tags'] as $tag) {
        $tags[$tag] = [
          'label' => $this->t($tag)
        ];
      }
    }

    // Allow other modules to alter and change tag information.
    $this->moduleHandler->alter('healthcheck_tags', $tags);

    return $tags;
  }

  /**
   * Get the list of tags for use in a select list.
   *
   * @return array
   *   An array of tag machine names and display names.
   */
  public function getTagsSelectList() {
    $list = [];

    // Get all the tags.
    $tags = $this->getTags();

    foreach ($tags as $tag_id => $tag) {
      // Use the label if set, otherwise, fallback to the tag's machine name.
      $list[$tag_id] = empty($tag['label']) ? $this->t($tag_id) : $tag['label'];
    }

    return $list;
  }

  /**
   * Get the Healthcheck plugins, filtering by tag and name.
   *
   * @param $tags
   *   Optional. An array of Healthcheck plugin tags.
   * @param array $omit
   *   Optional. An array of Healthcheck IDs to omit from results.
   *
   * @return array
   *   An array of Healthcheck definitions, keyed by plugin ID.
   */
  public function getDefinitionsByTags($tags = [], $omit = []) {
    $checks = [];

    foreach ($this->getDefinitions() as $name => $definition) {
      // Omit specific checks by name.
      if (in_array($name, $omit)) {
        continue;
      }
      elseif (!is_array($omit) && $name == $omit) {
        continue;
      }

      // Get the check's tags.
      $check_tags = empty($definition['tags']) ? [] : $definition['tags'];

      // If provided with a tag list, only add checks in that list.
      if (empty($tags) || array_intersect($tags, $check_tags)) {
        $checks[$name] = $definition;
      }
    }


    return $checks;
  }

  /**
   * Get a list of individual checks for use in a select list.
   *
   * @return array
   *   An array of check labels, keyed by plugin ID.
   */
  public function getChecksSelectList() {
    $list = [];
    $defs = $this->getDefinitions();

    foreach ($defs as $def) {
      $id = $def['id'];
      $label = $def['label'];

      $list[$id] = $label;
    }

    return $list;
  }

}
