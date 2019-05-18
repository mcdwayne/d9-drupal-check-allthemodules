<?php

namespace Drupal\hidden_tab\Entity\Helper;

/**
 * Implements TimestampedEntityInterface.
 *
 * @see \Drupal\hidden_tab\Entity\Base\TimestampedEntityInterface
 */
trait TimestampedEntityTrait {

  /**
   * When entity was created.
   *
   * @var int
   */
  protected $created;

  /**
   * see getCreatedTime() in TimestampedEntityInterface.
   *
   * @see \Drupal\hidden_tab\Entity\Base\TimestampedEntityInterface
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * See setCreatedTime() in TimestampedEntityInterface.
   *
   * @param $timestamp
   *   See setCreatedTime() in TimestampedEntityInterface.
   *
   * @return mixed
   *   This.
   *
   * @see \Drupal\hidden_tab\Entity\Base\TimestampedEntityInterface
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

}
