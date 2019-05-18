<?php

namespace Drupal\entity_staging\Commands;

use Drupal\entity_staging\EntityStagingExport;
use Drupal\entity_staging\EntityStagingImport;
use Drush\Commands\DrushCommands;

/**
 * Define all drush commands for entity staging.
 */
class EntityStagingCommands extends DrushCommands {

  /**
   * Import service.
   *
   * @var \Drupal\entity_staging\EntityStagingImport
   */
  protected $importService;

  /**
   * Export service.
   *
   * @var \Drupal\entity_staging\EntityStagingExport
   */
  protected $exportService;

  /**
   * EntityStagingCommands constructor.
   *
   * @param \Drupal\entity_staging\EntityStagingImport $importService
   *   Import service.
   * @param \Drupal\entity_staging\EntityStagingExport $exportService
   *   Export service.
   */
  public function __construct(EntityStagingImport $importService, EntityStagingExport $exportService) {
    $this->importService = $importService;
    $this->exportService = $exportService;
  }

  /**
   * Export all contents.
   *
   * @command entity_staging:export
   * @aliases ex, export-content
   */
  public function content() {
    $this->exportService->export();
    _drush_log_drupal_messages();
  }

  /**
   * Update migration config according the exported entities.
   *
   * @command entity_staging:update-migration-config
   * @aliases umc, update-migration-config
   */
  public function migrationConfig() {
    $this->importService->createMigrations();
    _drush_log_drupal_messages();
  }

}
