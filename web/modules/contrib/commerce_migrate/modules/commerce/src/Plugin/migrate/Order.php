<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate_drupal\Plugin\migrate\FieldMigration;

/**
 * Plugin class for Commerce 1 order migrations to handle fields and profiles.
 */
class Order extends FieldMigration {

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $this->fieldDiscovery->addEntityFieldProcesses($this, 'commerce_order');

      $definition = [
        'source' => [
          'plugin' => 'profile_field',
          'entity_type' => 'commerce_order',
          'ignore_map' => TRUE,
        ],
        'idMap' => [
          'plugin' => 'null',
        ],
        'destination' => [
          'plugin' => 'null',
        ],
      ];

      try {
        $field_migration = $this->migrationPluginManager->createStubMigration($definition);
        $field_migration->checkRequirements();
        foreach ($field_migration->getSourcePlugin() as $row) {
          $field_name = $row->getSourceProperty('field_name');
          $field_type = $row->getSourceProperty('type');
          $this->process[$field_name] = $field_type;
        }
      }
      catch (RequirementsException $e) {
        // The checkRequirements() call will fail when the profile module does
        // not exist on the source site.
      }
    }
    return parent::getProcess();
  }

}
