<?php

namespace Drupal\wbm2cm;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MigrationController {

  use StringTranslationTrait;

  /**
   * The migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * MigrationController constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_manager
   *   The migration plugin manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(MigrationPluginManagerInterface $migration_manager, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager, TranslationInterface $translation) {
    $this->migrationManager = $migration_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->setStringTranslation($translation);
  }

  /**
   * Executes a single migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to execute.
   */
  protected function execute(MigrationInterface $migration) {
    $executable = new MigrateExecutable($migration, new MigrateMessage(), $this->eventDispatcher);
    $executable->import();
  }

  /**
   * Executes all migrations for a particular step of the wbm2cm process.
   *
   * @param string $which
   *   The step to execute. Can be one of 'save', 'clear', or 'restore'.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface[]
   *   The executed migrations.
   */
  public function executeStep($which) {
    $migrations = $this->migrationManager->createInstances("wbm2cm_$which");
    array_walk($migrations, [$this, 'execute']);

    return $migrations;
  }

  /**
   * Executes all migrations for a particular step of the wbm2cm process and
   * returns imported counts for each executed migration.
   *
   * @param string $which
   *   The step to execute. Can be one of 'save', 'clear', or 'restore'.
   *
   * @return array
   *   The imported counts, keyed by the affected entity type ID.
   */
  public function executeStepWithCounts($which) {
    $counts = [];

    foreach ($this->executeStep($which) as $migration) {
      $entity_type = $migration->getDerivativeId();
      $counts[$entity_type] = $migration->getIdMap()->importedCount();
    }
    return $counts;
  }

  /**
   * Executes all migrations for a particular step of the wbm2cm process and
   * returns imported counts for each executed migration in a human-friendly
   * format.
   *
   * @param string $which
   *   The step to execute. Can be one of 'save', 'clear', or 'restore'.
   *
   * @return string[]
   *   The imported counts, in a human-friendly format.
   */
  public function executeStepWithMessages($which) {
    $messages = [];

    foreach ($this->executeStepWithCounts($which) as $entity_type => $count) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type);

      $messages[] = $this->t('Processed @count @items.', [
        '@count' => $count,
        '@items' => $this->formatPlural($count, $entity_type->getSingularLabel(), $entity_type->getPluralLabel()),
      ]);
    }
    return array_map('strval', $messages);
  }

    /**
     * Returns all moderation_state base field overrides.
     *
     * @return BaseFieldOverride[]
     *   The overridden moderation_state field definitions.
     */
  public function getOverriddenFields() {
    $overridden = [];

    $bundle_info = $this->entityTypeBundleInfo->getAllBundleInfo();

    foreach ($bundle_info as $entity_type_id => $bundles) {
      // Ignore non-fieldable entity types.
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if (! $entity_type->entityClassImplements(FieldableEntityInterface::class)) {
          continue;
      }

      foreach (array_keys($bundles) as $bundle) {
        $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

        if (isset($fields['moderation_state']) && $fields['moderation_state'] instanceof BaseFieldOverride) {
          array_push($overridden, $fields['moderation_state']);
        }
      }
    }
    return $overridden;
  }

}
