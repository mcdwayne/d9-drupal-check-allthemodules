<?php

namespace Drupal\acquia_contenthub_subscriber\EventSubscriber\EntityImport;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\EntityImportEvent;
use Drupal\acquia_contenthub_subscriber\SubscriberTracker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tracks entities as their saved/updated.
 */
class TrackEntity implements EventSubscriberInterface {

  /**
   * The subscriber tracker.
   *
   * @var \Drupal\acquia_contenthub_subscriber\SubscriberTracker
   */
  protected $tracker;

  /**
   * TrackEntity constructor.
   *
   * @param \Drupal\acquia_contenthub_subscriber\SubscriberTracker $tracker
   *   The subscriber tracker.
   */
  public function __construct(SubscriberTracker $tracker) {
    $this->tracker = $tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::ENTITY_IMPORT_NEW][] = ['trackEntity', 100];
    $events[AcquiaContentHubEvents::ENTITY_IMPORT_UPDATE][] = ['trackEntity', 100];
    return $events;
  }

  /**
   * Tracks entities being saved for the first time.
   *
   * @param \Drupal\acquia_contenthub\Event\EntityImportEvent $event
   *   The entity import event.
   *
   * @throws \Exception
   */
  public function trackEntity(EntityImportEvent $event) {
    $entity = $event->getEntity();
    $cdf_object = $event->getEntityData();
    $hash = $cdf_object->getAttribute('hash')->getValue()['und'];
    $this->tracker->track($entity, $hash, $cdf_object->getUuid());
  }

}
