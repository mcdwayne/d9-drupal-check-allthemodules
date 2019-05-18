<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Manages entity counter renderer plugins.
 *
 * @see hook_entity_counter_renderer_info_alter()
 * @see \Drupal\entity_counter\Plugin\EntityCounterRendererInterface
 * @see \Drupal\entity_counter\Plugin\EntityCounterRendererBase
 * @see \Drupal\entity_counter\Plugin\EntityCounterRendererManagerInterface
 * @see plugin_api
 */
class EntityCounterRendererManager extends DefaultPluginManager implements EntityCounterRendererManagerInterface {

  use StringTranslationTrait;

  /**
   * Constructs an EntityCounterRendererManager.
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
    parent::__construct('Plugin/EntityCounterRenderer', $namespaces, $module_handler, 'Drupal\entity_counter\Plugin\EntityCounterRendererInterface', 'Drupal\entity_counter\Annotation\EntityCounterRenderer');

    $this->alterInfo('entity_counter_renderer_info');
    $this->setCacheBackend($cache_backend, 'entity_counter_renderer_plugins');
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
