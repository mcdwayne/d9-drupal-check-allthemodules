<?php

namespace Drupal\chinese_identity_card\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Chinese identity card validator plugin manager.
 */
class ChineseIdentityCardValidatorManager extends DefaultPluginManager {


  /**
   * Constructs a new ChineseIdentityCardValidatorManager object.
   *
   * @param \Traversable                                  $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface      $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ChineseIdentityCardValidator', $namespaces, $module_handler, 'Drupal\chinese_identity_card\Plugin\ChineseIdentityCardValidatorInterface', 'Drupal\chinese_identity_card\Annotation\ChineseIdentityCardValidator');

    $this->alterInfo('chinese_identity_card_validator_info');
    $this->setCacheBackend($cache_backend, 'chinese_identity_card_validator_plugins');
  }

}
