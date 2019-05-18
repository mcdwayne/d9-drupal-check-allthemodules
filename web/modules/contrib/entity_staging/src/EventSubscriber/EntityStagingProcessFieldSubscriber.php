<?php

namespace Drupal\entity_staging\EventSubscriber;

use Drupal\entity_staging\Event\EntityStagingEvents;
use Drupal\entity_staging\Event\EntityStagingProcessFieldDefinitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to EntityStagingEvents::PROCESS_FIELD_DEFINITION events.
 *
 * Get the migration definition for processing a basic field.
 */
class EntityStagingProcessFieldSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EntityStagingEvents::PROCESS_FIELD_DEFINITION][] = ['getProcessFieldDefinition', -100];

    return $events;
  }

  /**
   * Get the the process definition.
   *
   * @param \Drupal\entity_staging\Event\EntityStagingProcessFieldDefinitionEvent $event
   */
  public function getProcessFieldDefinition(EntityStagingProcessFieldDefinitionEvent $event) {
    if (!$event->getFieldDefinition()->isTranslatable()) {
      $event->setProcessFieldDefinition([
        $event->getFieldDefinition()->getName() => $event->getFieldDefinition()->getName(),
      ]);
    }
    else {
      $event->setProcessFieldDefinition([
        $event->getFieldDefinition()->getName() => [
          'plugin' => 'get',
          'source' => $event->getFieldDefinition()->getName(),
          'language' => '@langcode',
        ],
      ]);
    }
  }

}
