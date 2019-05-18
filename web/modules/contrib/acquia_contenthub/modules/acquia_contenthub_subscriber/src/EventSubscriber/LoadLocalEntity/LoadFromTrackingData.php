<?php

namespace Drupal\acquia_contenthub_subscriber\EventSubscriber\LoadLocalEntity;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\LoadLocalEntityEvent;
use Drupal\acquia_contenthub_subscriber\SubscriberTracker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LoadFromTrackingData.
 *
 * Matches tracking data to local entities.
 *
 * @package Drupal\acquia_contenthub_subscriber\EventSubscriber\LoadFromTrackingData
 */
class LoadFromTrackingData implements EventSubscriberInterface {

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
    return [
      AcquiaContentHubEvents::LOAD_LOCAL_ENTITY => ['onLoadLocalEntity', 110],
    ];
  }

  /**
   * Load previously imported entities from the tracking table data.
   *
   * @param \Drupal\acquia_contenthub\Event\LoadLocalEntityEvent $event
   *   Data tamper event.
   *
   * @throws \Exception
   */
  public function onLoadLocalEntity(LoadLocalEntityEvent $event) {
    $cdf = $event->getCdf();
    if ($this->tracker->isTracked($cdf->getUuid())) {
      $entity = $this->tracker->getEntityByRemoteIdAndHash($cdf->getUuid());
      // No entity found, let the rest of the stack do its job.
      if (!$entity) {
        return;
      }
      $event->setEntity($entity);
      $event->stopPropagation();
    }
  }

}
