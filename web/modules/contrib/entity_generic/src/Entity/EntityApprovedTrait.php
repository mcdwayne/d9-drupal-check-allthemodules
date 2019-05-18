<?php

namespace Drupal\entity_generic\Entity;

/**
 * Implements approved status functionality.
 */
trait EntityApprovedTrait {

  /**
   * {@inheritdoc}
   */
  public function isApproved() {
    return (bool) $this->getEntityKey('approved');
  }

  /**
   * {@inheritdoc}
   */
  public function getApproved() {
    return (bool) $this->getEntityKey('approved');
  }

  /**
   * {@inheritdoc}
   */
  public function setApproved($approved) {
    $this->set($this->getEntityType()->getKey('approved'), $approved ? 1 : 0);
    $this->getApprovedTime($approved ? \Drupal::time()->getRequestTime() : 0);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getApprovedTime() {
    return $this->get($this->getEntityType()->getKey('approved').'_time')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setApprovedTime($timestamp) {
    $this->set($this->getEntityType()->getKey('approved').'_time', $timestamp);
    return $this;
  }

}
