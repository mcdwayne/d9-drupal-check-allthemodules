<?php

namespace Drupal\entity_generic\Entity;

/**
 * Implements types functionality.
 */
trait EntityTypedTrait {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set($this->getEntityType()->getKey('bundle'), $type);
    $this->entityKeys['bundle'] = $type;
    return $this;
  }

}
