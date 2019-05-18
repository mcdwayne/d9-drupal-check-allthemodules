<?php

namespace Drupal\open_connect\Plugin\OpenConnect;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class ProviderManager extends DefaultPluginManager implements ProviderManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/OpenConnect/Provider', $namespaces, $module_handler, 'Drupal\open_connect\Plugin\OpenConnect\Provider\ProviderInterface', 'Drupal\open_connect\Annotation\OpenConnectProvider');
    $this->setCacheBackend($cache_backend, 'open_connect_provider');
    $this->alterInfo('open_connect_provider_info');
  }

}
