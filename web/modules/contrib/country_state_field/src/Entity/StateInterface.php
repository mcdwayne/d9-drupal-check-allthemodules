<?php

namespace Drupal\country_state_field\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining State entities.
 *
 * @ingroup country_state_field
 */
interface StateInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the State name.
   *
   * @return string
   *   Name of the State.
   */
  public function getName();

  /**
   * Sets the State name.
   *
   * @param string $name
   *   The State name.
   *
   * @return \Drupal\country_state_field\Entity\StateInterface
   *   The called State entity.
   */
  public function setName($name);

  /**
   * Gets the State creation timestamp.
   *
   * @return int
   *   Creation timestamp of the State.
   */
  public function getCreatedTime();

  /**
   * Sets the State creation timestamp.
   *
   * @param int $timestamp
   *   The State creation timestamp.
   *
   * @return \Drupal\country_state_field\Entity\StateInterface
   *   The called State entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the State published status indicator.
   *
   * Unpublished State are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the State is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a State.
   *
   * @param bool $published
   *   TRUE to set this State to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\country_state_field\Entity\StateInterface
   *   The called State entity.
   */
  public function setPublished($published);

}
