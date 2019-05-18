<?php

namespace Drupal\certificatelogin\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Certification Authority Signature Verification plugin manager.
 */
class CaSignatureVerificationPluginManager extends DefaultPluginManager {


  /**
   * Constructs a new CaSignatureVerificationPluginManager object.
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
    parent::__construct('Plugin/CaSignatureVerificationPlugin', $namespaces, $module_handler, 'Drupal\certificatelogin\Plugin\CaSignatureVerificationPluginInterface', 'Drupal\certificatelogin\Annotation\CaSignatureVerificationPlugin');

    $this->alterInfo('certificatelogin_certificatelogin.ca_signature_verification_info');
    $this->setCacheBackend($cache_backend, 'certificatelogin_certificatelogin.ca_signature_verification_plugins');
  }

}
