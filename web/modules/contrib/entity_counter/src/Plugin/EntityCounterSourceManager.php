<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Manages entity counter source plugins.
 *
 * @see hook_entity_counter_source_info_alter()
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceInterface
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceBase
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceManagerInterface
 * @see plugin_api
 */
class EntityCounterSourceManager extends DefaultPluginManager implements EntityCounterSourceManagerInterface {

  use StringTranslationTrait;

  /**
   * Constructs an EntityCounterSourceManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/EntityCounterSource', $namespaces, $module_handler, 'Drupal\entity_counter\Plugin\EntityCounterSourceInterface', 'Drupal\entity_counter\Annotation\EntityCounterSource');

    $this->alterInfo('entity_counter_source_info');
    $this->setCacheBackend($cache_backend, 'entity_counter_source_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function removeExcludeDefinitions(array $definitions) {
    $excluded = [];

    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      $plugin_instance = $this->createInstance($plugin_id);
      if ($plugin_instance->isExcluded()) {
        $excluded[$plugin_id] = $definition;
      }
    }

    return $excluded ? array_diff_key($definitions, $excluded) : $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL) {
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();
    uasort($definitions, function ($a, $b) {
      return strnatcasecmp($a['label'], $b['label']);
    });

    return $definitions;
  }

}
