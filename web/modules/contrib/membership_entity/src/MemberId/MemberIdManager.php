<?php
declare(strict_types=1);

namespace Drupal\membership_entity\MemberId;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The MemberId Plugin manager.
 *
 * @see plugin_api
 */
class MemberIdManager extends DefaultPluginManager {

  /**
   * Constructs a MemberIdManager object.
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
    parent::__construct('Plugin/MemberId', $namespaces, $module_handler, 'Drupal\membership_entity\MemberId\MemberIdInterface', 'Drupal\membership_entity\Annotation\MemberId');
    $this->alterInfo('member_id_info');
    $this->setCacheBackend($cache_backend, 'member_id_info_plugin');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition, 'Drupal\membership_entity\MemberId\MemberIdInterface');
    // @TODO: Pass the full entity object to the plugin class.
    $entity = NULL;
    return new $plugin_class($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      return $this->createInstance($plugin_id, $options);
    }
  }

}
