<?php

namespace Drupal\entity_staging;

use Drupal\entity_staging\Event\EntityStagingEvents;
use Drupal\entity_staging\Event\EntityStagingProcessFieldDefinitionEvent;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate_plus\Entity\Migration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Import content entities.
 */
class EntityStagingImport {

  /**
   * The content staging manager service.
   *
   * @var \Drupal\entity_staging\EntityStagingManager
   */
  protected $contentStagingManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity types allowed for staging.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface[]
   */
  protected $entityTypesAllowedForStaging;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * EntityStagingImport constructor.
   *
   * @param \Drupal\entity_staging\EntityStagingManager $entity_staging_manager
   *   The content staging manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(EntityStagingManager $entity_staging_manager, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EventDispatcherInterface $event_dispatcher) {
    $this->contentStagingManager = $entity_staging_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypesAllowedForStaging = $entity_staging_manager->getContentEntityTypes(EntityStagingManager::ALLOWED_FOR_STAGING_ONLY);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Generate content staging migrations.
   */
  public function createMigrations() {
    // First, remove all existing migrations.
    $this->cleanExistingMigrations();

    foreach ($this->entityTypesAllowedForStaging as $entity_type_id => $entity_type) {
      if ($entity_type->hasKey('bundle')) {
        $bundles = $this->contentStagingManager->getBundles($entity_type_id, EntityStagingManager::ALLOWED_FOR_STAGING_ONLY);
        foreach ($bundles as $bundle_id => $bundle_label) {
          $this->createMigrationDefinition($entity_type, $bundle_id, $bundle_label['label']);
          if ($entity_type->isTranslatable()) {
            $this->createMigrationDefinition($entity_type, $bundle_id, $bundle_label['label'], 'translations');
          }
        }
      }
      else {
        $this->createMigrationDefinition($entity_type, $entity_type_id);
        if ($entity_type->isTranslatable()) {
          $this->createMigrationDefinition($entity_type, $entity_type_id, '', 'translations');
        }
      }
    }
  }

  /**
   * Create Migration entities.
   *
   * @param $bundle_id
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   * @param $bundle_label
   * @param string $language
   */
  protected function createMigrationDefinition(ContentEntityTypeInterface $entity_type, $bundle_id, $bundle_label = '', $language = 'default_language') {
    $entity_type_id = $entity_type->id();
    $export_path = realpath(DRUPAL_ROOT . '/' . $this->contentStagingManager->getDirectory());
    if (file_exists($export_path . '/' . $entity_type_id . '/' . $language . '/' . $bundle_id . '.json')) {
      $migration_id = 'staging_content_' . $entity_type_id . '_' . $bundle_id;

      $process = $this->getProcessDefinition($entity_type, $bundle_id, $migration_id, $language);

      $founded_key = array_search($migration_id, $process['process_definition']);
      if ($founded_key) {
        unset($process['process_definition'][$founded_key]);
      }

      $config = [
        'id' => $migration_id . '_' . $language,
        'migration_tags' => ['entity_staging'],
        'label' => t('Import @entity_label @bundle_label @language', [
          '@entity_label' => $entity_type->getLabel(),
          '@bundle_label' => $bundle_label,
          '@language' => $language,
        ]),
        'migration_group' => 'entity_staging',
        'source' => [
          'plugin' => 'entity_staging_json',
          'input_path' => '../staging/' . $entity_type_id . '/' . $language . '/' . $bundle_id . '.json',
        ],
        'process' => $process['process_definition'],
        'destination' => [
          'plugin' => ($entity_type_id == 'paragraph') ? 'entity_reference_revisions:paragraph' : 'entity:' . $entity_type_id,
        ],
        'migration_dependencies' => [
          'required' => $process['dependencies'],
        ],
      ];
      if ($language == 'translations') {
        $config['destination']['translations'] = TRUE;
      }
      Migration::create($config)->save();

      drupal_set_message(t('Migration for @entity_type - @langcode - @bundle created', [
        '@entity_type' => $entity_type_id,
        '@langcode' => $language,
        '@bundle' => $bundle_id,
      ]));
    }
  }

  /**
   * Get migration process definitions.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type.
   * @param $bundle_id
   *   The bundle id.
   * @param $migration_id
   *   The current migration id.
   * @param null $language
   *   The current language.
   *
   * @return array
   */
  protected function getProcessDefinition(ContentEntityTypeInterface $entity_type, $bundle_id, $migration_id, $language) {
    $entity_type_id = $entity_type->id();
    $bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);

    $config = [];
    $dependencies = [];
    foreach ($bundle_fields as $field_key => $bundle_field) {
      if ($field_key == $entity_type->getKey('id')) {
        if ($language !== 'default_language') {
          $config[$field_key] = [
            'plugin' => 'migration_lookup',
            'source' => 'uuid',
            'migration' => $migration_id . '_default_language',
          ];
        }
      }
      else {
        $event = new EntityStagingProcessFieldDefinitionEvent($entity_type, $bundle_id, $bundle_field);
        /** @var EntityStagingProcessFieldDefinitionEvent $event */
        $this->eventDispatcher->dispatch(EntityStagingEvents::PROCESS_FIELD_DEFINITION, $event);
        if ($event->getProcessFieldDefinition()) {
          $config = array_merge($config, $event->getProcessFieldDefinition());
          $dependencies = array_merge($dependencies, $event->getMigrationDependencies());
        }
      }
    }
    if ($entity_type_id == 'entity_subqueue') {
      $config['name'] = 'name';
    }
    return [
      'process_definition' => $config,
      'dependencies' => array_values(array_unique($dependencies)),
    ];
  }

  /**
   * Remove all existing content staging migrations.
   */
  protected function cleanExistingMigrations() {
    $existing_migrations = $this->entityTypeManager
      ->getStorage('migration')
      ->loadByProperties(['migration_group' => 'entity_staging']);

    if (!empty($existing_migrations)) {
      foreach ($existing_migrations as $migration) {
        $migration->delete();
      }
    }
  }

}
