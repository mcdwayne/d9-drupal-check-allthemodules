<?php

/**
 * @file
 * Contains the \Drupal\author_pane\AuthorPaneDatumPluginManager class.
 */

namespace Drupal\author_pane;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for Author Pane Datum plugins.
 */
class AuthorPaneDatumPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/AuthorPane';

    // The name of the annotation class that contains the plugin definition.
    $plugin_definition_annotation_name = 'Drupal\author_pane\Annotation\AuthorPaneDatum';

    parent::__construct($subdir, $namespaces, $module_handler, NULL, $plugin_definition_annotation_name);

    $this->alterInfo('author_pane_data');

    $this->setCacheBackend($cache_backend, 'author_pane_data');
  }

}
