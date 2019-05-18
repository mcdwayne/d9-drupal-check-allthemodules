<?php

namespace Drupal\acquia_contenthub_publisher\EventSubscriber\HandleWebhook;

use Drupal\acquia_contenthub\EventSubscriber\HandleWebhook\PurgeBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Class PurgePublisher.
 *
 * Reacts on "purge-successful" webhook and purges the publish export tracking
 * database table and the export queue.
 *
 * @package Drupal\acquia_contenthub_publisher\EventSubscriber\HandleWebhook
 */
class Purge extends PurgeBase {

  /**
   * The Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Purge constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger_channel
   *   The logger channel.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(QueueFactory $queue_factory, LoggerChannelInterface $logger_channel, Connection $database) {
    parent::__construct($queue_factory, $logger_channel);
    $this->database = $database;
  }

  /**
   * {@inheritDoc}
   */
  protected function getQueueName(): string {
    return 'acquia_contenthub_publish_export';
  }

  /**
   * Reacts on "purge successful" webhook.
   *
   * @see \Drupal\acquia_contenthub\EventSubscriber\HandleWebhook\PurgeBase::onHandleWebhook()
   */
  protected function onPurgeSuccessful() {
    parent::onPurgeSuccessful();

    $this->database
      ->truncate('acquia_contenthub_publisher_export_tracking')
      ->execute();
    $this->logger->info('Database table "acquia_contenthub_publisher_export_tracking" has been truncated successfully.');
  }

}
