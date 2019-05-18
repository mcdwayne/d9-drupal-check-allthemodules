<?php

namespace Drupal\acquia_contenthub_subscriber\EventSubscriber\EntityDataTamper;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\EntityDataTamperEvent;
use Drupal\acquia_contenthub_subscriber\SubscriberTracker;
use Drupal\depcalc\DependentEntityWrapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Place auto_updated_disabled entities into the dependency stack.
 */
class DisabledEntity implements EventSubscriberInterface {

  /**
   * The subscriber tracker.
   *
   * @var \Drupal\acquia_contenthub_subscriber\SubscriberTracker
   */
  protected $tracker;

  /**
   * DisabledEntity constructor.
   *
   * @param \Drupal\acquia_contenthub_subscriber\SubscriberTracker $tracker
   *   Subscriber tracker.
   */
  public function __construct(SubscriberTracker $tracker) {
    $this->tracker = $tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::ENTITY_DATA_TAMPER][] = [
      'onDataTamper',
      100,
    ];

    return $events;
  }

  /**
   * Tamper with CDF data before its imported.
   *
   * @param \Drupal\acquia_contenthub\Event\EntityDataTamperEvent $event
   *   The data tamper event.
   *
   * @throws \Exception
   */
  public function onDataTamper(EntityDataTamperEvent $event) {
    $cdf = $event->getCdf();
    foreach ($cdf->getEntities() as $object) {
      $uuid = $object->getUuid();
      // @todo we want to compare by hashes eventually.
      if (!$this->tracker->isTracked($uuid)) {
        continue;
      }
      if ($this->tracker->getStatusByUuid($uuid) !== SubscriberTracker::AUTO_UPDATE_DISABLED) {
        continue;
      }
      $entity = $this->tracker->getEntityByRemoteIdAndHash($uuid);
      if ($entity) {
        $wrapper = new DependentEntityWrapper($entity);
        $wrapper->setRemoteUuid($uuid);
        $event->getStack()->addDependency($wrapper);
      }
    }
  }

}
