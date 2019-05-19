<?php

namespace Drupal\entity_extra\Entity;

trait EntityCreatedTrait {

  /**
   * Returns the timestamp when this entity was created.
   */
  public function getCreatedTime() {
    $created = NULL;
    $entity_type = $this->getEntityType();
    if ($entity_type->hasKey('created')) {
      $field_name = $entity_type->getKey('created');
      $created = $this->get($field_name)->getValue();
    }
    return $created;
  }
}
