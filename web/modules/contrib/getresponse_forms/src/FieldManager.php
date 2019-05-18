<?php

namespace Drupal\getresponse_forms;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages GetResponse form field plugins.
 *
 * @see \Drupal\getresponse_forms\Annotation\GetresponseFormsField
 * @see \Drupal\getresponse_forms\ConfigurableFieldInterface
 * @see \Drupal\image\ConfigurableImageEffectBase
 * @see \Drupal\getresponse_forms\FieldInterface
 * @see \Drupal\image\ImageEffectBase
 * @see plugin_api
 */
class FieldManager extends DefaultPluginManager {

  /**
   * Constructs a new GetResponse form FieldManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/GetresponseFormsField', $namespaces, $module_handler, 'Drupal\getresponse_forms\FieldInterface', 'Drupal\getresponse_forms\Annotation\GetresponseFormsField');

    $this->setCacheBackend($cache_backend, 'getresponse_forms_field_plugins');
  }

}

