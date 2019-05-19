<?php

/**
 * @file
 * Contains Drupal\sl_stats\Plugin\QueueWorker\StatsQueueWorker.php
 */

namespace Drupal\sl_stats\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Manages stats in a queue
 *
 * @QueueWorker(
 *   id = "sl_stats_worker",
 *   title = @Translation("SL Stats computer"),
 * )
 */
class StatsQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Creates a new object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   */
  public function __construct(EntityStorageInterface $node_storage) {
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $lock = \Drupal::lock();
    $computer = \Drupal::service('sl_stats.computer');
    if ($lock->acquire('sl_stats_autostats_' . $data->nid)) {
      $status = $computer->compute($data->nid);
      $lock->release('sl_stats_autostats_' . $data->nid);
      return $status;
    }
    else {
      throw new \Drupal\Core\Queue\RequeueException();
    }
  }
}