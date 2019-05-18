<?php

namespace Drupal\entity_embed_extras\DialogEntityDisplay;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages Dialog Entity Display Plugin plugins.
 *
 * @see hook_dialog_entity_display_info_alter()
 * @see \Drupal\entity_embed_extras\Annotation\DialogEntityDisplay
 * @see \Drupal\entity_embed_extras\DialogEntityDisplayInterface
 * @see \Drupal\entity_embed_extras\ConfigurableReviewDisplayBase
 * @see \Drupal\entity_embed_extras\DialogEntityDisplayInterface
 * @see \Drupal\entity_embed_extras\DialogEntityDisplayBase
 * @see plugin_api
 */
class DialogEntityDisplayManager extends DefaultPluginManager {

  /**
   * Constructs a new DialogEntityDisplayManager.
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
    parent::__construct('Plugin/entity_embed_extras/DialogEntityDisplay', $namespaces, $module_handler, 'Drupal\entity_embed_extras\DialogEntityDisplay\DialogEntityDisplayInterface', 'Drupal\entity_embed_extras\Annotation\DialogEntityDisplay');

    $this->alterInfo('entity_embed_dialog_entity_display_info');
    $this->setCacheBackend($cache_backend, 'entity_embed_dialog_entity_display_plugins');
  }

}
