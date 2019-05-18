<?php

namespace Drupal\cached_computed_field;

/**
 * Interface for classes representing an entity that has an expired field.
 */
interface ExpiredItemInterface {

  /**
   * Returns the entity type ID of the entity that contains the expired field.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Returns the ID of the entity that contains the expired field.
   *
   * @return mixed
   *   The ID of the entity that contains the expired field.
   */
  public function getEntityId();

  /**
   * Returns the name of the expired field.
   *
   * @return string
   *   The name of the expired field.
   */
  public function getFieldName();

}
