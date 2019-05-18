<?php

namespace Drupal\acquia_contenthub_subscriber\EventSubscriber\HandleWebhook;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\HandleWebhookEvent;
use Drupal\acquia_contenthub_subscriber\SubscriberTracker;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ImportUpdateAssets.
 *
 * @package Drupal\acquia_contenthub_subscriber\EventSubscriber\HandleWebhook
 */
class ImportUpdateAssets implements EventSubscriberInterface {

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The subscription tracker.
   *
   * @var \Drupal\acquia_contenthub_subscriber\SubscriberTracker
   */
  protected $tracker;

  /**
   * ImportUpdateAssets constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue factory.
   * @param \Drupal\acquia_contenthub_subscriber\SubscriberTracker $tracker
   *   The subscription tracker.
   */
  public function __construct(QueueFactory $queue, SubscriberTracker $tracker) {
    $this->queue = $queue->get('acquia_contenthub_subscriber_import');
    $this->tracker = $tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::HANDLE_WEBHOOK][] = 'onHandleWebhook';
    return $events;
  }

  /**
   * Handles webhook events.
   *
   * @param \Drupal\acquia_contenthub\Event\HandleWebhookEvent $event
   *   The HandleWebhookEvent object.
   *
   * @throws \Exception
   */
  public function onHandleWebhook(HandleWebhookEvent $event) {
    $payload = $event->getPayload();
    $client = $event->getClient();
    // @todo Would be nice to have one place with statuses list - $payload['status'].
    // @todo The same regarding $payload['crud'] and supported types ($asset['type']).
    if ($payload['status'] == 'successful' && $payload['crud'] == 'update' && isset($payload['assets']) && count($payload['assets']) && $payload['initiator'] != $client->getSettings()->getUuid()) {
      $uuids = [];
      foreach ($payload['assets'] as $asset) {
        if (in_array($asset['type'], ['drupal8_content_entity', 'drupal8_config_entity'])) {
          if ($this->tracker->isTracked($asset['uuid'])) {
            $status = $this->tracker->getStatusByUuid($asset['uuid']);
            if ($status === SubscriberTracker::AUTO_UPDATE_DISABLED) {
              continue;
            }
          }
          $uuids[] = $asset['uuid'];
          $this->tracker->queue($asset['uuid']);
        }
      }
      if ($uuids) {
        $item = new \stdClass();
        $item->uuids = implode(', ', $uuids);
        $this->queue->createItem($item);
      }
    }
  }

}
