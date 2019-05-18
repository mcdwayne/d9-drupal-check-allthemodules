<?php

namespace Drupal\erf\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Registration entities.
 *
 * @ingroup erf
 */
interface RegistrationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Registration creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Registration.
   */
  public function getCreatedTime();

  /**
   * Sets the Registration creation timestamp.
   *
   * @param int $timestamp
   *   The Registration creation timestamp.
   *
   * @return \Drupal\erf\Entity\RegistrationInterface
   *   The called Registration entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Determines if a Source Entity is associated with the registration.
   *
   * @return Boolean
   *   The entity associated with this registration.
   */
  public function hasSourceEntity();

  /**
   * Gets the Source Entity associated with the registration.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity associated with this registration.
   */
  public function getSourceEntity();

  /**
   * Sets the Registration locked property.
   *
   * @return \Drupal\erf\Entity\RegistrationInterface
   *   The called Registration entity.
   */
  public function lock();

  /**
   * Unsets the Registration locked property.
   *
   * @return \Drupal\erf\Entity\RegistrationInterface
   *   The called Registration entity.
   */
  public function unlock();

}
