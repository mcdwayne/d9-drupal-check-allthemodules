<?php

namespace Drupal\drd\Update;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of DRD Update plugins.
 */
abstract class Manager extends DefaultPluginManager implements ManagerInterface {

  private $selectList;

  /**
   * Constructs a new \Drupal\drd\Update\Manager object.
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
    parent::__construct($this->getSubDir(), $namespaces, $module_handler, $this->getPluginInterface(), 'Drupal\drd\Annotation\Update');

    $this->alterInfo('drd_update');
    $this->setCacheBackend($cache_backend, 'drd_update_plugins_' . $this->getType());
  }

  /**
   * {@inheritdoc}
   */
  public function getSelect() {
    if (!isset($this->selectList)) {
      $this->selectList = [];
      foreach ($this->getDefinitions() as $def) {
        $this->selectList[$def['id']] = $def['admin_label'];
      }
    }
    return $this->selectList;
  }

}
