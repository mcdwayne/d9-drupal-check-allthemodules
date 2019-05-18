<?php

namespace Drupal\acquia_contenthub_publisher\EventSubscriber\DeleteRemoteEntity;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\DeleteRemoteEntityEvent;
use Drupal\acquia_contenthub_publisher\PublisherTracker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdateTracking implements EventSubscriberInterface {

  /**
   * The publisher tracker.
   *
   * @var \Drupal\acquia_contenthub_publisher\PublisherTracker
   */
  protected $tracker;

  /**
   * UpdateTracking constructor.
   *
   * @param \Drupal\acquia_contenthub_publisher\PublisherTracker $tracker
   *   The publisher tracker.
   */
  public function __construct(PublisherTracker $tracker) {
    $this->tracker = $tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::DELETE_REMOTE_ENTITY][] = 'onDeleteRemoteEntity';
    return $events;
  }

  /**
   * Removes deleted remote entities from the publisher tracking table.
   *
   * @param \Drupal\acquia_contenthub\Event\DeleteRemoteEntityEvent $event
   *
   * @throws \Exception
   */
  public function onDeleteRemoteEntity(DeleteRemoteEntityEvent $event) {
    if ($this->tracker->get($event->getUuid())) {
      $this->tracker->delete($event->getUuid());
    }
  }

}
