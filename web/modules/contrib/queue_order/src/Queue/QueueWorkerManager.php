<?php

namespace Drupal\queue_order\Queue;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Queue\QueueWorkerManager as CoreQueueWorkerManager;

/**
 * Class QueueWorkerManager.
 *
 * @package Drupal\queue_order\Queue
 */
class QueueWorkerManager extends CoreQueueWorkerManager {

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs an QueueWorkerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The module configs.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config) {
    parent::__construct($namespaces, $cache_backend, $module_handler);
    $this->config = $config->get('queue_order.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return self::sortDefinitions(parent::getDefinitions(), $this->config->get('order') ?: []);
  }

  /**
   * Reorder Queue worker definitions.
   *
   * @param array $definitions
   *   Queue worker definitions.
   * @param array $weight
   *   Weight overrides.
   *
   * @return array
   *   Reordered Queue worker definitions.
   */
  public static function sortDefinitions(array $definitions, array $weight) {
    // Prepare definitions for sorting.
    foreach ($definitions as $key => &$definition) {
      $weight = 0;
      // Define default weight value or hint defined weight to the int value.
      if (!empty($definition['cron']['weight'])) {
        $weight = intval($definition['cron']['weight']);
      }
      if (!empty($definition['weight'])) {
        $weight = intval($definition['weight']);
      }
      // Check weight value overrides.
      $definition['weight'] = empty($weight[$key]) ? $weight : intval($weight[$key]);
    }
    // Sort definitions by weight element.
    uasort($definitions, [SortArray::class, 'sortByWeightElement']);
    return $definitions;
  }

}
