<?php

namespace Drupal\entity_generic\Entity;

/**
 * Implements archived status functionality.
 */
trait EntityArchivedTrait {

  /**
   * {@inheritdoc}
   */
  public function isArchived() {
    return (bool) $this->getEntityKey('archived');
  }

  /**
   * {@inheritdoc}
   */
  public function getArchived() {
    return (bool) $this->getEntityKey('archived');
  }

  /**
   * {@inheritdoc}
   */
  public function setArchived($archived) {
    $this->set($this->getEntityType()->getKey('archived'), $archived ? 1 : 0);
    $this->getArchivedTime($archived ? \Drupal::time()->getRequestTime() : 0);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getArchivedTime() {
    return $this->get($this->getEntityType()->getKey('archived').'_time')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setArchivedTime($timestamp) {
    $this->set($this->getEntityType()->getKey('archived').'_time', $timestamp);
    return $this;
  }

}
