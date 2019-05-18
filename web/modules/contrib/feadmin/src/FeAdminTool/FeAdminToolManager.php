<?php

/**
 * @file
 * Contains \Drupal\feadmin\FeAdminTool\FeAdminToolManager.
 * 
 * Sponsored by: www.freelance-drupal.com
 */

namespace Drupal\feadmin\FeAdminTool;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an FeAdminTool plugin manager.
 *
 * @see \Drupal\feadmin\FeAdminTool\Annotation\FeAdminTool
 * @see \Drupal\feadmin\FeAdminTool\FeAdminToolInterface
 * @see plugin_api
 */
class FeAdminToolManager extends DefaultPluginManager implements FeAdminToolManagerInterface {

  /**
   * Constructs a FeAdminToolManager object.
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
    parent::__construct('Plugin/FeAdminTool', $namespaces, $module_handler, 'Drupal\feadmin\FeAdminTool\FeAdminToolInterface', 'Drupal\feadmin\FeAdminTool\Annotation\FeAdminTool');
    $this->alterInfo('feadmintool');
    $this->setCacheBackend($cache_backend, 'feadmintool');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $settings = \Drupal::config('feadmin.settings')->get('tools');

    $definitions = $this->getCachedDefinitions();
    if (!isset($definitions)) {
      $definitions = $this->findDefinitions();
      $this->setCachedDefinitions($definitions);
    }

    // Sort definitions by weight.
    $plugin_ids = array();
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $plugin_ids[$plugin_id] = isset($settings[$plugin_id]) ? $settings[$plugin_id]['weight'] : 0;
    }
    array_multisort($plugin_ids, SORT_ASC, $definitions);
    
    return $definitions;
  }

}
