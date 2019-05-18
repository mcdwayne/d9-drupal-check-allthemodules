<?php

namespace Drupal\content_synchronizer\Processors\Entity;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * The Entity processor interface.
 */
interface EntityProcessorInterface extends PluginInspectionInterface {

  /**
   * Return the data to export.
   *
   * Get the array of data to export in array format :
   * [
   *    "property_1"=>[ "value1", "value2"]
   *    "property_2"=>[ "value1"]
   * ].
   *
   * @param \Drupal\Core\Entity\EntityInterface $entityToExport
   *   The entity to export.
   *
   * @return array
   *   The entity data to export.
   */
  public function getDataToExport(EntityInterface $entityToExport);

  /**
   * Get the array of the property of the entity not to export :.
   *
   * @return array
   *   The properties not to export.
   */
  public function getPropertiesIdsNotToExportList();

  /**
   * Return the entity to import.
   *
   * @return \Drupal\Core\Entity\Entity
   *   The entity to import.
   */
  public function getEntityToImport(array $data, EntityInterface $existingEntity);

}
