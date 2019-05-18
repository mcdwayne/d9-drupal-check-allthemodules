<?php

namespace Drupal\image_canvas_editor_api\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class EditorPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ImageCanvasEditor', $namespaces, $module_handler, 'Drupal\image_canvas_editor_api\Plugin\EditorInterface', 'Drupal\image_canvas_editor_api\Annotation\ImageCanvasEditor');
    $this->alterInfo('image_canvas_editor_api_plugin_info');
  }

}
