<?php
/**
 * Created by PhpStorm.
 * User: valerij
 * Date: 07.04.17
 * Time: 13:32
 */

namespace Drupal\group_content_field\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\group_content_field\Annotation\GroupContentDecorator;
use Drupal\rel_content\Annotation\RelatedContent;
use Drupal\rel_content\RelatedContentInterface;

class GroupContentManager extends DefaultPluginManager {

  /**
   * @inheritdoc
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/GroupContentDecorator', $namespaces, $module_handler, GroupContentDecoratorInterface::class, GroupContentDecorator::class);

    $this->setCacheBackend($cache_backend, 'group_content_info');
  }

  /**
   *
   */
  public function getAll($group_content_item) {
    $configurations = $this->getDefinitions();

    foreach ($configurations as &$configuration) {
      $configuration['group_content_item'] = $group_content_item;
    }

    $plugins = new DefaultLazyPluginCollection($this, $configurations);
    $plugins->sort();

    return $plugins;
  }
}
