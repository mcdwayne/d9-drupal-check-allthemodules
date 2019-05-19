<?php

namespace Drupal\views_evi;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\views_evi\Annotation\ViewsEviValue;

class ValuePluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/views_evi/Value', $namespaces, $module_handler, ViewsEviValueInterface::class, ViewsEviValue::class);

    $this->setCacheBackend($cache_backend, 'views_evi_value');
  }

}
