<?php

namespace Drupal\odoo_api_entity_sync\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\odoo_api_entity_sync\MappingManagerTrait;

/**
 * Transforms Odoo ID to Drupal ID.
 *
 * @code
 * process:
 *   uid:
 *     plugin: entity_id_from_odoo_id
 *     entity_type: 'user'
 *     export_type: 'default'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "entity_id_from_odoo_id"
 * )
 */
class EntityIdFromOdooId extends ProcessPluginBase {

  use MappingManagerTrait;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['entity_type'])) {
      throw new MigrateException('Missing entity_type configuration.');
    }
    if (empty($this->configuration['export_type'])) {
      throw new MigrateException('Missing export_type configuration.');
    }
    $mapped_entities = $this->getOdooMappingManager()->findMappedEntities('x_drupal_user.role', [$value]);
    $entity_type = $this->configuration['entity_type'];
    $export_type = $this->configuration['export_type'];

    if (!empty($mapped_entities[$value][$entity_type][$export_type])) {
      return $mapped_entities[$value][$entity_type][$export_type];
    }

    return NULL;
  }

}
