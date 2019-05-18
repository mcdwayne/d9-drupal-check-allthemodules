<?php

namespace Drupal\hidden_tab\Plugable\Access;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabAccessAnon;
use Drupal\hidden_tab\Plugable\HiddenTabPluginManager;

/**
 * The plugin manager.
 *
 * @see \Drupal\hidden_tab\Plugable\Access\HiddenTabAccessInterface
 */
class HiddenTabAccessPluginManager extends HiddenTabPluginManager {

  protected $pid = HiddenTabAccessInterface::PID;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/HiddenTabAccess',
      $namespaces,
      $module_handler,
      HiddenTabAccessInterface::class,
      HiddenTabAccessAnon::class
    );
    $this->alterInfo('hidden_tab_access_info');
    $this->setCacheBackend($cache_backend, 'hidden_tab_access_plugin');
  }

  /**
   * Facory method, create an instance from container.
   *
   * @return \Drupal\hidden_tab\Plugable\HiddenTabPluginManager
   */
  public static function instance(): HiddenTabPluginManager {
    return \Drupal::service('plugin.manager.' . HiddenTabAccessInterface::PID);
  }

}
