<?php
/**
 * @file
 * Contains \Drupal\cronpub\Plugin\Cronpub\CronpubActionManager.
 */

namespace Drupal\cronpub\Plugin\Cronpub;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for cronpub action.
 *
 * @ingroup cronpub_action
 */
class CronpubActionManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    $plugin_interface = 'Drupal\cronpub\Plugin\Cronpub\CronpubActionInterface',
    $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin'
  ) {
    parent::__construct('Plugin/Cronpub/Action', $namespaces, $module_handler, NULL, 'Drupal\cronpub\Plugin\Cronpub\CronpubAction');
  }


}
