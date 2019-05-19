<?php

namespace Drupal\views_evi;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\views_evi\Annotation\ViewsEviVisibility;

class VisiblityPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/views_evi/Visibility', $namespaces, $module_handler, ViewsEviVisibilityInterface::class, ViewsEviVisibility::class);

    $this->setCacheBackend($cache_backend, 'views_evi_visibility');
  }

}
