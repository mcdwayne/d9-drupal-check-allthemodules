<?php

namespace Drupal\git_issues;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an GitIssues plugin manager.
 *
 * @see plugin_api
 */
class GitIssuesManager extends DefaultPluginManager {

  /**
   * Constructs a GitIssues manager object.
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
    parent::__construct(
      'Plugin/GitIssues',
      $namespaces,
      $module_handler,
      'Drupal\git_issues\Plugin\GitIssues\GitIssuesPluginInterface',
      'Drupal\git_issues\Annotation\GitIssuesPlugin'
    );
    $this->alterInfo('git_issues_info');
    $this->setCacheBackend($cache_backend, 'git_issues_plugins');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}
