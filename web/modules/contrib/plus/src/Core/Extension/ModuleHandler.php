<?php

namespace Drupal\plus\Core\Extension;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandler as CoreModuleHandler;
use Drupal\plus\AlterPluginManager;

/**
 * Modifies core's module_handler service.
 */
class ModuleHandler extends CoreModuleHandler {

  /**
   * The Alter Plugin Manager service.
   *
   * @var \Drupal\plus\AlterPluginManager
   */
  protected $alterPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(string $root, array $module_list, CacheBackendInterface $cache_backend, AlterPluginManager $alter_plugin_manager) {
    parent::__construct($root, $module_list, $cache_backend);
    $this->alterPluginManager = $alter_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    parent::alter($type, $data, $context1, $context2);
    $this->alterPluginManager->alter($type, $data, $context1, $context2);
  }

}
