<?php

namespace Drupal\entity_generic\Entity;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Implements generic entity class.
 */
abstract class Generic extends Simple implements GenericInterface {

  use EntityTypedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    return $fields;
  }

}
