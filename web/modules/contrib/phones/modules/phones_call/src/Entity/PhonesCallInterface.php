<?php

namespace Drupal\phones_call\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Phones call entities.
 *
 * @ingroup phones_call
 */
interface PhonesCallInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Phones call name.
   *
   * @return string
   *   Name of the Phones call.
   */
  public function getName();

  /**
   * Sets the Phones call name.
   *
   * @param string $name
   *   The Phones call name.
   *
   * @return \Drupal\phones_call\Entity\PhonesCallInterface
   *   The called Phones call entity.
   */
  public function setName($name);

  /**
   * Gets the Phones call creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Phones call.
   */
  public function getCreatedTime();

  /**
   * Sets the Phones call creation timestamp.
   *
   * @param int $timestamp
   *   The Phones call creation timestamp.
   *
   * @return \Drupal\phones_call\Entity\PhonesCallInterface
   *   The called Phones call entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Phones call published status indicator.
   *
   * Unpublished Phones call are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Phones call is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Phones call.
   *
   * @param bool $published
   *   TRUE to set this Phones call to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\phones_call\Entity\PhonesCallInterface
   *   The called Phones call entity.
   */
  public function setPublished($published);

}
