<?php

namespace Drupal\user_restrictions;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages user restriction type plugins.
 *
 * @see hook_user_restriction_type_info_alter()
 * @see \Drupal\user_restrictions\Annotation\UserRestrictionType
 * @see \Drupal\user_restrictions\Plugin\UserRestrictionTypeInterface
 * @see \Drupal\user_restrictions\Plugin\UserRestrictionType\UserRestrictionTypeBase
 * @see plugin_api
 */
class UserRestrictionTypeManager extends DefaultPluginManager implements UserRestrictionTypeManagerInterface {

  /**
   * Constructs a UserRestrictionTypeManager object.
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
    parent::__construct('Plugin/UserRestrictionType', $namespaces, $module_handler, 'Drupal\user_restrictions\Plugin\UserRestrictionTypeInterface', 'Drupal\user_restrictions\Annotation\UserRestrictionType');
    $this->alterInfo('user_restriction_type_info');
    $this->setCacheBackend($cache_backend, 'user_restriction_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    $instances = &drupal_static(__FUNCTION__, []);
    if (empty($instances)) {
      // Get registered plugins.
      $plugins = $this->getDefinitions();
      // Sort plugins by weight.
      uasort($plugins, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
      foreach ($plugins as $plugin_id => $plugin) {
        // Instanciate the plugin.
        $instances[$plugin_id] = $this->createInstance($plugin_id, $plugin);
      }
    }

    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function getType($id) {
    $instances = $this->getTypes();
    return $instances[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function getTypesAsOptions() {
    $options = [];

    foreach ($this->getTypes() as $plugin_id => $type) {
      $options[$plugin_id] = $type->getLabel();
    }

    return $options;
  }

}
