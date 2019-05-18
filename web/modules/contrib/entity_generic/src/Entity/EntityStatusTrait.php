<?php

namespace Drupal\entity_generic\Entity;

/**
 * Implements status functionality.
 */
trait EntityStatusTrait {

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($active) {
    $this->set($this->getEntityKey('status'), $active ? 1 : 0);
    return $this;
  }

}
