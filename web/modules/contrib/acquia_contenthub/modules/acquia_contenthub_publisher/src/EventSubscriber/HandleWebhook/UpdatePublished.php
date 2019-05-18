<?php

namespace Drupal\acquia_contenthub_publisher\EventSubscriber\HandleWebhook;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\HandleWebhookEvent;
use Drupal\acquia_contenthub_publisher\PublisherTracker;
use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UpdatePublished.
 *
 * Subscribes to onHandleWebhook.
 */
class UpdatePublished implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * UpdatePublished constructor.
   *
   * @param Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
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
    $client = $event->getClient();
    if ($payload['status'] == 'successful' && $payload['crud'] == 'update' && $payload['initiator'] == $client->getSettings()->getUuid()) {
      $uuids = [];
      if (isset($payload['assets']) && count($payload['assets'])) {
        foreach ($payload['assets'] as $asset) {
          if (!in_array($asset['type'], ['drupal8_content_entity', 'drupal8_config_entity'])) {
            continue;
          }
          $uuids[] = $asset['uuid'];
        }
      }
      if ($uuids) {
        $query = $this->database->select('acquia_contenthub_publisher_export_tracking', 'acpet')
          ->fields('acpet', ['entity_uuid']);
        $query->condition('acpet.entity_uuid', $uuids, 'IN');
        $items = $query->execute()->fetchAll();
        $uuids = [];
        foreach ($items as $item) {
          $uuids[] = $item->entity_uuid;
        }
        $update = $this->database->update('acquia_contenthub_publisher_export_tracking')
          ->fields(['status' => PublisherTracker::CONFIRMED]);
        $update->condition('entity_uuid', $uuids, 'IN');
        $update->execute();
      }
    }
  }

}
