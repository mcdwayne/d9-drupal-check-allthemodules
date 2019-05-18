<?php

namespace Drupal\entity_generic\Entity;

/**
 * Implements "mark as deleted" flag functionality.
 */
trait EntityDeletedTrait {

  /**
   * {@inheritdoc}
   */
  public function isDeleted() {
    return (bool) $this->getEntityKey('flag_deleted');
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleted() {
    return (bool) $this->getEntityKey('flag_deleted');
  }

  /**
   * {@inheritdoc}
   */
  public function setDeleted($flag_deleted) {
    $this->set($this->getEntityType()->getKey('flag_deleted'), $flag_deleted ? 1 : 0);
    $this->getDeletedTime($flag_deleted ? \Drupal::time()->getRequestTime() : 0);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeletedTime() {
    return $this->get($this->getEntityType()->getKey('flag_deleted').'_time')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeletedTime($timestamp) {
    $this->set($this->getEntityType()->getKey('flag_deleted').'_time', $timestamp);
    return $this;
  }

}
