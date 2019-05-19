<?php

namespace Drupal\entity_extra\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * A revisionable entity with the Entity Extra's ContentEntityBase features.
 *
 * @see \Drupal\entity_extra\Entity\ContentEntityBase
 */
class RevisionableContentEntityBase extends ContentEntityBase implements RevisionLogInterface {

  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);
    return $fields;
  }

}
