<?php

namespace Drupal\system_tags;

/**
 * Class SystemTagHelperInterface.
 *
 * @package Drupal\system_tags
 */
interface SystemTagHelperInterface {

  /**
   * Get the names of the fields which references System Tags.
   *
   * @param string $entityTypeId
   *   The entity type to which the field belongs.
   *
   * @return array|bool
   *   The names of the reference fields or FALSE if one does not exists.
   */
  public function getReferenceFieldNames($entityTypeId);

  /**
   * Gets a lightweight map of reference fields across entity types.
   *
   * @return array
   *   Array of field map.
   */
  public function getFieldMap();

}
