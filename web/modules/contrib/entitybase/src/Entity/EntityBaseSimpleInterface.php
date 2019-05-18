<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseSimpleInterface.
 */

namespace Drupal\entity_base\Entity;

use Drupal\user\EntityOwnerInterface;

/**
 * Defines a common interface for all content entity objects.
 *
 * @see \Drupal\entity_base\EntityBaseSimple
 *
 * @ingroup entity_api
 */
interface EntityBaseSimpleInterface extends EntityBaseBasicInterface, EntityOwnerInterface {

  /**
   * Returns the entity status.
   *
   * Unpublished entities are only visible to their authors and to administrators.
   *
   * @return bool
   *   TRUE if the entity is active.
   */
  public function isActive();

  /**
   * Sets the active status of an entity.
   *
   * @param bool $active
   *   TRUE to set this entity to active, FALSE to set it to inactive.
   *
   * @return \Drupal\entity_base\Entity\EntityBaseSimpleInterface
   *   The called entity.
   */
  public function setActive($active);

  /**
   * Gets the entity label.
   *
   * @return string
   *   Label of the entity.
   */
  public function getLabel();

  /**
   * Sets the entity label.
   *
   * @param string $label
   *   The entity label.
   *
   * @return \Drupal\entity_base\Entity\EntityBaseSimpleInterface
   *   The called entity.
   */
  public function setLabel($label);

  /**
   * Returns the revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\entity_base\Entity\EntityBaseGenericInterface
   *   The called entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Returns the revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionAuthor();

  /**
   * Sets the revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\entity_base\Entity\EntityBaseGenericInterface
   *   The called entity.
   */
  public function setRevisionAuthorId($uid);

  /**
   * Locks an entity.
   *
   * @throws \Drupal\entity_base\Exception\LockException
   *   Thrown if the lock is unavailable.
   */
  public function lock();

  /**
   * Unlocks an entity.
   */
  public function unlock();

  /**
   * Checks whether an entity is locked.
   *
   * @return bool
   *   Returns true if the entity is locked, and false if not.
   */
  public function isLocked();

}
