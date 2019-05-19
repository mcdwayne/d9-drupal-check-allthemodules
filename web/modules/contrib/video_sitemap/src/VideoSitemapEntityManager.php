<?php

namespace Drupal\video_sitemap;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Class VideoSitemapEntityManager.
 *
 * @package Drupal\video_sitemap
 */
class VideoSitemapEntityManager {

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a VideoSitemapGenerator object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Database connection.
   */
  public function __construct(QueueFactory $queue_factory, Connection $connection) {
    $this->queueFactory = $queue_factory;
    $this->connection = $connection;
  }

  /**
   * Add entity item to the index queue.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   */
  public function addEntityToQueue(EntityInterface $entity) {
    $queue = $this->queueFactory->get('node_with_video_queue');
    $queue->createItem(['entity_type' => $entity->getEntityTypeId(), 'entity_id' => $entity->id()]);
  }

  /**
   * Deletes entity record from index table.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   */
  public function deleteLocRecord(EntityInterface $entity) {
    $id = $entity->id();
    $this->connection->delete('video_sitemap_index')
      ->condition('loc_id', $id)
      ->execute();
  }

}
