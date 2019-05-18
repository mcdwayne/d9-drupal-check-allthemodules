<?php

namespace Drupal\entity_staging\EventSubscriber;

use Drupal\entity_staging\EntityStagingManager;
use Drupal\entity_staging\Event\EntityStagingEvents;
use Drupal\entity_staging\Event\EntityStagingProcessFieldDefinitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to EntityStagingEvents::PROCESS_FIELD_DEFINITION events.
 *
 * Get the migration definition for processing an entity reference revision field.
 */
class EntityStagingProcessEntityReferenceRevisionFieldSubscriber implements EventSubscriberInterface {

  /**
   * The content staging manager service.
   *
   * @var \Drupal\entity_staging\EntityStagingManager
   */
  protected $contentStagingManager;

  /**
   * EntityStagingProcessEntityReferenceRevisionFieldSubscriber constructor.
   *
   * @param \Drupal\entity_staging\EntityStagingManager $entity_staging_manager
   *   The content staging manager service.
   */
  public function __construct(EntityStagingManager $entity_staging_manager) {
    $this->contentStagingManager = $entity_staging_manager;
  }

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
    if ($event->getFieldDefinition()->getType() == 'entity_reference_revisions'
      && in_array($event->getFieldDefinition()->getSettings()['target_type'], array_keys($this->contentStagingManager->getContentEntityTypes(EntityStagingManager::ALLOWED_FOR_STAGING_ONLY)))) {

      $migration = [];
      foreach ($event->getFieldDefinition()->getSettings()['handler_settings']['target_bundles'] as $target_bundle) {
        $migration[] = 'staging_content_' . $event->getFieldDefinition()->getSettings()['target_type'] . '_' . $target_bundle . '_default_language';
      }

      $process_field[] = [
        'plugin' => 'migration_lookup',
        'migration' => $migration,
        'source' => $event->getFieldDefinition()->getName(),
      ];
      $process_field[] = [
        'plugin' => 'entity_staging_iterator',
        'process' => [
          'target_id' => '0',
          'target_revision_id' => '1',
        ],
      ];
      $event->setProcessFieldDefinition([
        $event->getFieldDefinition()->getName() => $process_field
      ]);
      $event->setMigrationDependencies($migration);
      $event->stopPropagation();
    }
  }

}
