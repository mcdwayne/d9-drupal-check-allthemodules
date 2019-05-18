<?php

namespace Drupal\entity_generic\Entity;

/**
 * Implements label functionality.
 */
trait EntityLabelTrait {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getEntityKey('label');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getEntityKey('label');
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set($this->getEntityType()->getKey('label'), $label);
    return $this;
  }

}
