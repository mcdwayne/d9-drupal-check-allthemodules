<?php

namespace Drupal\aframe;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class AFrameComponentPluginManager.
 *
 * @package Drupal\aframe
 */
class AFrameComponentPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/AFrame/Component', $namespaces, $module_handler, 'Drupal\aframe\AFrameComponentPluginInterface', 'Drupal\aframe\Annotation\AFrameComponent');
    $this->alterInfo('aframe_components');
    $this->setCacheBackend($cache_backend, 'aframe_components');
  }

}
