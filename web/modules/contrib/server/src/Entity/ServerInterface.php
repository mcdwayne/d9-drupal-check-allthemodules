<?php

namespace Drupal\server\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Server entities.
 *
 * @ingroup server
 */
interface ServerInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Server name.
   *
   * @return string
   *   Name of the Server.
   */
  public function getName();

  /**
   * Sets the Server name.
   *
   * @param string $name
   *   The Server name.
   *
   * @return \Drupal\server\Entity\ServerInterface
   *   The called Server entity.
   */
  public function setName($name);

  /**
   * Gets the Server creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Server.
   */
  public function getCreatedTime();

  /**
   * Sets the Server creation timestamp.
   *
   * @param int $timestamp
   *   The Server creation timestamp.
   *
   * @return \Drupal\server\Entity\ServerInterface
   *   The called Server entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Server published status indicator.
   *
   * Unpublished Server are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Server is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Server.
   *
   * @param bool $published
   *   TRUE to set this Server to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\server\Entity\ServerInterface
   *   The called Server entity.
   */
  public function setPublished($published);

  /**
   * Gets the Server revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Server revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\server\Entity\ServerInterface
   *   The called Server entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Server revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Server revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\server\Entity\ServerInterface
   *   The called Server entity.
   */
  public function setRevisionUserId($uid);

}
