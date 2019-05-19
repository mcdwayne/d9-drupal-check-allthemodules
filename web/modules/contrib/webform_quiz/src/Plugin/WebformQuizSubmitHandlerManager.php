<?php

namespace Drupal\webform_quiz\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Webform quiz submit handler plugin manager.
 */
class WebformQuizSubmitHandlerManager extends DefaultPluginManager {


  /**
   * Constructs a new WebformQuizSubmitHandlerManager object.
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
    parent::__construct('Plugin/WebformQuizSubmitHandler', $namespaces, $module_handler, 'Drupal\webform_quiz\Plugin\WebformQuizSubmitHandlerInterface', 'Drupal\webform_quiz\Annotation\WebformQuizSubmitHandler');

    $this->alterInfo('webform_quiz_webform_quiz_submit_handler_info');
    $this->setCacheBackend($cache_backend, 'webform_quiz_webform_quiz_submit_handler_plugins');
  }

}
