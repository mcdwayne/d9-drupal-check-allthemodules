<?php

namespace Drupal\entity_staging\EventSubscriber;

use Drupal\entity_staging\Event\EntityStagingEvents;
use Drupal\entity_staging\Event\EntityStagingProcessFieldDefinitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to EntityStagingEvents::PROCESS_FIELD_DEFINITION events.
 *
 * Get the migration definition for processing a revision field.
 */
class EntityStagingProcessRevisionFieldSubscriber implements EventSubscriberInterface {

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
    // Do not process revision field and stop progation...
    if ($event->getFieldDefinition()->getName() == $event->getEntityType()->getKey('revision')) {
      // ... Except for paragraph revision field.
      if ($event->getEntityType()->id() == 'paragraph') {
        $event->setProcessFieldDefinition([
          $event->getFieldDefinition()->getName() => $event->getFieldDefinition()->getName()
        ]);
      }
      $event->stopPropagation();
    }
  }

}
