<?php

namespace Drupal\assembly\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Assembly entities.
 *
 * @ingroup assembly
 */
interface AssemblyInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Assembly name.
   *
   * @return string
   *   Name of the Assembly.
   */
  public function getName();

  /**
   * Sets the Assembly name.
   *
   * @param string $name
   *   The Assembly name.
   *
   * @return \Drupal\assembly\Entity\AssemblyInterface
   *   The called Assembly entity.
   */
  public function setName($name);

  /**
   * Gets the Assembly creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Assembly.
   */
  public function getCreatedTime();

  /**
   * Sets the Assembly creation timestamp.
   *
   * @param int $timestamp
   *   The Assembly creation timestamp.
   *
   * @return \Drupal\assembly\Entity\AssemblyInterface
   *   The called Assembly entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Assembly published status indicator.
   *
   * Unpublished Assembly are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Assembly is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Assembly.
   *
   * @param bool $published
   *   TRUE to set this Assembly to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\assembly\Entity\AssemblyInterface
   *   The called Assembly entity.
   */
  public function setPublished($published);

  /**
   * Gets the Assembly revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Assembly revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\assembly\Entity\AssemblyInterface
   *   The called Assembly entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Assembly revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Assembly revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\assembly\Entity\AssemblyInterface
   *   The called Assembly entity.
   */
  public function setRevisionUserId($uid);

}
