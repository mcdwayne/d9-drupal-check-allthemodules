<?php

namespace Drupal\acquia_contenthub_publisher\EventSubscriber\PublishEntities;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\ContentHubPublishEntitiesEvent;
use Drupal\acquia_contenthub_publisher\PublisherTracker;
use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class RemoveUnmodifiedEntities.
 *
 * @package Drupal\acquia_contenthub_publisher\EventSubscriber\PublishEntities
 */
class RemoveUnmodifiedEntities implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * RemoveUnmodifiedEntities constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::PUBLISH_ENTITIES][] = ['onPublishEntities', 1000];
    return $events;
  }

  /**
   * Removes unmodified entities before publishing.
   *
   * @param \Drupal\acquia_contenthub\Event\ContentHubPublishEntitiesEvent $event
   *   The Content Hub publish entities event.
   */
  public function onPublishEntities(ContentHubPublishEntitiesEvent $event) {
    $dependencies = $event->getDependencies();
    $uuids = array_keys($dependencies);
    $query = $this->database->select('acquia_contenthub_publisher_export_tracking', 't')
      ->fields('t', ['entity_uuid', 'hash']);
    $query->condition('t.entity_uuid', $uuids, 'IN');
    $query->condition('t.status', [PublisherTracker::CONFIRMED, PublisherTracker::EXPORTED], 'IN');
    $results = $query->execute();
    foreach ($results as $result) {
      // Can't check it if it doesn't have a hash.
      // @todo make this a query.
      if (!$result->hash) {
        continue;
      }
      $wrapper = $dependencies[$result->entity_uuid];
      if ($wrapper->getHash() == $result->hash) {
        $event->removeDependency($result->entity_uuid);
      }
    }
  }

}
