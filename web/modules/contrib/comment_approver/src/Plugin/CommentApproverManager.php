<?php

namespace Drupal\comment_approver\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Comment approver plugin manager.
 */
class CommentApproverManager extends DefaultPluginManager {


  /**
   * Constructs a new CommentApproverManager object.
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
    parent::__construct('Plugin/CommentApprover', $namespaces, $module_handler, 'Drupal\comment_approver\Plugin\CommentApproverInterface', 'Drupal\comment_approver\Annotation\CommentApprover');

    $this->alterInfo('comment_approver_comment_approver_info');
    $this->setCacheBackend($cache_backend, 'comment_approver_comment_approver_plugins');
  }

}
