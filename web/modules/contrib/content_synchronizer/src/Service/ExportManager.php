<?php

namespace Drupal\content_synchronizer\Service;

use Drupal\content_synchronizer\Entity\ExportEntity;
use Drupal\Core\Entity\EntityInterface;

/**
 * The export manager.
 */
class ExportManager {

  const SERVICE_NAME = 'content_synchronizer.export_manager';

  /**
   * Return the list of export checkboxes options.
   *
   * @return array
   *   The export list options.
   */
  public function getExportsListOptions() {
    $exportsOptions = [];
    /** @var \Drupal\content_synchronizer\Entity\ExportEntity $export */
    foreach (ExportEntity::loadMultiple() as $export) {
      $exportsOptions[$export->id()] = $export->label();
    }

    return $exportsOptions;
  }

  /**
   * Return the list of export for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\content_synchronizer\Entity\ExportEntity
   *   THe list of exports.
   */
  public function getEntitiesExport(EntityInterface $entity) {
    if ($result = \Drupal::database()->select(ExportEntity::TABLE_ITEMS)
      ->fields(ExportEntity::TABLE_ITEMS, [ExportEntity::FIELD_EXPORT_ID])
      ->condition(ExportEntity::FIELD_ENTITY_ID, $entity->id())
      ->condition(ExportEntity::FIELD_ENTITY_TYPE, $entity->getEntityTypeId())
      ->execute()) {
      return ExportEntity::loadMultiple($result->fetchCol());
    }

    return [];
  }

  /**
   * Action after delete entity.
   */
  public function onEntityDelete(EntityInterface $entity) {
    foreach ($this->getEntitiesExport($entity) as $export) {
      $export->removeEntity($entity);
    }
  }

}
