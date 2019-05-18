<?php

namespace Drupal\entity_staging\EventSubscriber;

use Drupal\entity_staging\Event\EntityStagingEvents;
use Drupal\entity_staging\Event\EntityStagingProcessFieldDefinitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to EntityStagingEvents::PROCESS_FIELD_DEFINITION events.
 *
 * Get the migration definition for processing the URI field
 * in the file entity type.
 */
class EntityStagingProcessFileUriFieldSubscriber implements EventSubscriberInterface {

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
    if ($event->getEntityType()->id() == 'file' && $event->getFieldDefinition()->getName() == 'uri') {
      $process_field = [
        'plugin' => 'file_copy',
        'source' => [
          'filepath',
          $event->getFieldDefinition()->getName(),
        ],
      ];
      if ($event->getFieldDefinition()->isTranslatable()) {
        $process_field['language'] = '@langcode';
      }
      $event->setProcessFieldDefinition([
        $event->getFieldDefinition()->getName() => $process_field
      ]);
      $event->stopPropagation();
    }
  }

}
