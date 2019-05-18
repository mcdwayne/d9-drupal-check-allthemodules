<?php

namespace Drupal\permanent_entities\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Permanent Entity entities.
 *
 * @ingroup permanent_entities
 */
interface PermanentEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Permanent Entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Permanent Entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Permanent Entity creation timestamp.
   *
   * @param int $timestamp
   *   The Permanent Entity creation timestamp.
   *
   * @return \Drupal\permanent_entities\Entity\PermanentEntityInterface
   *   The called Permanent Entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Permanent Entity published status indicator.
   *
   * Unpublished Permanent Entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Permanent Entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Permanent Entity.
   *
   * @param bool $published
   *   TRUE to set this Permanent Entity to published.
   *
   * @return \Drupal\permanent_entities\Entity\PermanentEntityInterface
   *   The called Permanent Entity entity.
   */
  public function setPublished($published);

  /**
   * Gets the Permanent Entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Permanent Entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\permanent_entities\Entity\PermanentEntityInterface
   *   The called Permanent Entity entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Permanent Entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Permanent Entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\permanent_entities\Entity\PermanentEntityInterface
   *   The called Permanent Entity entity.
   */
  public function setRevisionUserId($uid);

}
