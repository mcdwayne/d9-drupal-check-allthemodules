<?php

namespace Drupal\acquia_contenthub\EventSubscriber\HandleWebhook;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\HandleWebhookEvent;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PurgeBase.
 *
 * Provides the base event subscriber class for "purge successful" webhook
 * handler for acquia_contenthub_publisher and acquia_contenthub_subscriber
 * modules.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\HandleWebhook
 */
abstract class PurgeBase implements EventSubscriberInterface {

  /**
   * The webhook's "purge successful" event name.
   */
  protected const PURGE_SUCCESSFUL = 'purge-successful';

  /**
   * The Queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * PurgeBase constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger_channel
   *   The logger factory.
   */
  public function __construct(QueueFactory $queue_factory, LoggerChannelInterface $logger_channel) {
    $this->queue = $queue_factory->get($this->getQueueName());
    $this->logger = $logger_channel;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::HANDLE_WEBHOOK][] = 'onHandleWebhook';
    return $events;
  }

  /**
   * On handle webhook event.
   *
   * @param \Drupal\acquia_contenthub\Event\HandleWebhookEvent $event
   *   The handle webhook event.
   */
  public function onHandleWebhook(HandleWebhookEvent $event) {
    $payload = $event->getPayload();
    if (self::PURGE_SUCCESSFUL !== $payload['crud']) {
      return;
    }

    if ('successful' !== $payload['status']) {
      $this->logger->error('Failed to react on @webhook webhook (@payload).',
        [
          '@webhook' => self::PURGE_SUCCESSFUL,
          '@payload' => print_r($payload, TRUE),
        ]);
      return;
    }

    $this->onPurgeSuccessful();
  }

  /**
   * Reacts on "purge successful" webhook.
   */
  protected function onPurgeSuccessful() {
    // Delete queue.
    $this->queue->deleteQueue();
    $this->logger->info(
      'Queue @queue has been purged successfully.',
      ['@queue' => $this->getQueueName()]);
  }

  /**
   * Returns the queue name to delete.
   *
   * @return string
   *   Queue name.
   */
  abstract protected function getQueueName(): string;

}
