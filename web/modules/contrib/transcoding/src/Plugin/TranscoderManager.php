<?php

namespace Drupal\transcoding\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class TranscoderManager extends DefaultPluginManager {

  /**
   * TranscoderManager constructor.
   *
   * @param \Traversable $namespaces
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Transcoder', $namespaces, $module_handler, 'Drupal\transcoding\Plugin\TranscoderPluginInterface', 'Drupal\transcoding\Annotation\Transcoder');
    $this->setCacheBackend($cache_backend, 'transcoder_plugins');
  }

}
