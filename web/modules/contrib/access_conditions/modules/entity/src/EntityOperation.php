<?php

namespace Drupal\access_conditions_entity;

/**
 * Enumerates the entity operation values.
 */
final class EntityOperation extends AbstractEnum {

  /**
   * Value indicating a view operation.
   */
  const VIEW = 0;

  /**
   * Value indicating an update operation.
   */
  const UPDATE = 1;

  /**
   * Value indicating a delete operation.
   */
  const DELETE = 2;

}
