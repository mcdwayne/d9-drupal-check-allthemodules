<?php

namespace Drupal\queue_order;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\queue_order\Queue\QueueWorkerManager;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class QueueOrderServiceProvider.
 *
 * @package Drupal\queue_order
 */
class QueueOrderServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('plugin.manager.queue_worker')) {
      $plugin_manager = $container->getDefinition('plugin.manager.queue_worker');
      $plugin_manager->setClass(QueueWorkerManager::class);
      $plugin_manager->addArgument(new Reference('config.factory'));
    }
  }

}
