<?php

namespace Drupal\measuremail\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages measuremail elements plugins.
 *
 * @see \Drupal\measuremail\Annotation\MeasuremailElements
 * @see \Drupal\measuremail\ConfigurableMeasuremailElementInterface
 * @see \Drupal\measuremail\ConfigurableMeasuremailElementBase
 * @see \Drupal\measuremail\MeasuremailElementsInterface
 * @see \Drupal\measuremail\MeasuremailElementsBase
 * @see plugin_api
 */
class MeasuremailElementsManager extends DefaultPluginManager {

  /**
   * Constructs a new MeasuremailElementsManager.
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
    parent::__construct('Plugin/MeasuremailElements', $namespaces, $module_handler, 'Drupal\measuremail\MeasuremailElementsInterface', 'Drupal\measuremail\Annotation\MeasuremailElements');

    $this->alterInfo('measuremail_elements_info');
    $this->setCacheBackend($cache_backend, 'measuremail_elements_plugins');
  }

}
