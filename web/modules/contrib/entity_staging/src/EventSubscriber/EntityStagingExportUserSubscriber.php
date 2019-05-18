<?php

namespace Drupal\entity_staging\EventSubscriber;

use Drupal\entity_staging\Event\EntityStagingBeforeExportEvent;
use Drupal\entity_staging\Event\EntityStagingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to EntityStagingEvents::BEFORE_EXPORT events.
 *
 * Perform action before export user entities.
 */
class EntityStagingExportUserSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EntityStagingEvents::BEFORE_EXPORT][] = ['deleteAdminUser', -10];

    return $events;
  }

  /**
   * Don't export the anonymous and admin user.
   *
   * @param \Drupal\entity_staging\Event\EntityStagingBeforeExportEvent $event
   */
  public function deleteAdminUser(EntityStagingBeforeExportEvent $event) {
    if ($event->getEntityTypeId() == 'user') {
      $entities = $event->getEntities();
      foreach ($entities[$event->getEntityTypeId()] as $entity_id => $entity) {
        /** @var \Drupal\user\Entity\User $entity */
        if (in_array($entity->id(), [0, 1])) {
          unset($entities[$event->getEntityTypeId()][$entity_id]);
        }
      }
      $event->setEntities($entities);
    }
  }

}
