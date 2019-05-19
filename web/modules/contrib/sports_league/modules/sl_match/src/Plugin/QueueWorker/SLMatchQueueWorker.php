<?php

/**
 * @file
 * Contains Drupal\sl_stats\Plugin\QueueWorker\StatsQueueWorker.php
 */

namespace Drupal\sl_match\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Manages stats in a queue
 *
 * @QueueWorker(
 *   id = "sl_match_worker",
 *   title = @Translation("SL Match worker"),
 * )
 */
class SLMatchQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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

  public function itemExists($item) {

  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $computer = \Drupal::service('sl_match.worker');
    $status = $computer->compute($data->nid);
    return $status;
  }
}