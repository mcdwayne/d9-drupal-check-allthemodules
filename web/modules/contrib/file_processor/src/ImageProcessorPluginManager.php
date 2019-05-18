<?php

namespace Drupal\file_processor;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;


class ImageProcessorPluginManager extends DefaultPluginManager {
  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $plugin_interface = 'Drupal\file_processor\ImageMinInterface.php';
    $subdir = 'Plugin/file_processor/Image';
    $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin';

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);
    $this->alterInfo('file_processor_image_info');
    $this->setCacheBackend($cache_backend, 'file_processor_image_info');
  }
}