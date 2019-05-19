<?php

namespace Drupal\warmer;

use Drupal\Core\Queue\QueueFactory;
use Drupal\warmer\Plugin\WarmerPluginBase;

/**
 * Manage queue items and processing.
 */
class QueueManager {

  const QUEUE_NAME = 'warmer';

  /**
   * The queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private $queue;

  /**
   * Sets the queue to use to execute the cache warming operations.
   *
   * @param \Drupal\Core\Queue\QueueFactory
   *   The queue factory.
   * @param bool $is_reliable
   *   Indicates if the queue should be reliable.
   */
  public function setQueue(QueueFactory $queue_factory, $is_reliable) {
    $queue_factory->get(static::QUEUE_NAME, $is_reliable)->createQueue();
    $this->queue = $queue_factory->get(static::QUEUE_NAME, $is_reliable);
  }

  /**
   * Add a batch of warming operations to the queue.
   *
   * @param callable $callback
   *   The operation to call when dequeuing.
   * @param array $ids
   *   The list of IDs.
   * @param \Drupal\warmer\Plugin\WarmerPluginBase $warmer
   *   The warmer plugin.
   */
  public function enqueueBatch(callable $callback, array $ids, WarmerPluginBase $warmer) {
    $this->queue
      ->createItem(new QueueData($callback, $ids, $warmer->getPluginId()));
    $warmer->markAsEnqueued();
  }

}
