<?php

namespace Drupal\entity_staging\EventSubscriber;

use Drupal\entity_staging\Event\EntityStagingEvents;
use Drupal\entity_staging\Event\EntityStagingProcessFieldDefinitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to EntityStagingEvents::PROCESS_FIELD_DEFINITION events.
 *
 * Get the migration definition for processing the block content uuid field.
 */
class EntityStagingProcessUuidFieldSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EntityStagingEvents::PROCESS_FIELD_DEFINITION][] = ['getProcessFieldDefinition', -10];

    return $events;
  }

  /**
   * Get the the process definition.
   *
   * @param \Drupal\entity_staging\Event\EntityStagingProcessFieldDefinitionEvent $event
   */
  public function getProcessFieldDefinition(EntityStagingProcessFieldDefinitionEvent $event) {
    if ($event->getFieldDefinition()->getName() == 'uuid') {
      // It's necessary for a block content to keep the original uuid.
      if ($event->getEntityType()->id() == 'block_content') {
        $event->setProcessFieldDefinition([
          $event->getFieldDefinition()->getName() => $event->getFieldDefinition()->getName(),
        ]);
      }
      // For all cases, stop propagation.
      $event->stopPropagation();
    }
  }

}
