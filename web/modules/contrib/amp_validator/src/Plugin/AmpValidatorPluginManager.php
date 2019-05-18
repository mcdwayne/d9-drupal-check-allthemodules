<?php

namespace Drupal\amp_validator\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\amp_validator\Annotation\AmpValidatorPlugin;

/**
 * Provides the Validator plugin manager.
 */
class AmpValidatorPluginManager extends DefaultPluginManager {

  protected $settings;

  /**
   * Constructor for ValidatorPluginManager objects.
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
    parent::__construct('Plugin/AmpValidator', $namespaces, $module_handler, AmpValidatorPluginInterface::class, AmpValidatorPlugin::class);
    $this->alterInfo('amp_validator_plugin');
    $this->setCacheBackend($cache_backend, 'amp_validator_plugins');
  }

}
