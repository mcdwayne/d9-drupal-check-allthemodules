<?php

namespace Drupal\mail_entity_queue\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a plugin manager for mail entity queue processor plugins.
 */
class MailEntityQueueProcessorPluginManager extends DefaultPluginManager implements MailEntityQueueProcessorPluginManagerInterface {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/MailEntityQueue', $namespaces, $module_handler, 'Drupal\mail_entity_queue\Plugin\MailEntityQueueProcessorInterface', 'Drupal\mail_entity_queue\Annotation\MailEntityQueueProcessor');

    $this->alterInfo('mail_entity_queue_info');
    $this->setCacheBackend($cache_backend, 'mail_entity_queue');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $instances = $this->createInstances([$plugin_id], $configuration);

    return reset($instances);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstances($plugin_id = [], array $configuration = []) {
    if (empty($plugin_id)) {
      $plugin_id = array_keys($this->getDefinitions());
    }

    $factory = $this->getFactory();
    $plugin_ids = (array) $plugin_id;

    $instances = [];
    foreach ($plugin_ids as $plugin_id) {
      $instances[$plugin_id] = $factory->createInstance($plugin_id, isset($configuration[$plugin_id]) ? $configuration[$plugin_id] : []);
    }

    return $instances;
  }

}
