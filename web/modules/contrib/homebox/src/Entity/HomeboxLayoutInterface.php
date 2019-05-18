<?php

namespace Drupal\homebox\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Homebox Layout entities.
 *
 * @ingroup homebox
 */
interface HomeboxLayoutInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Homebox Layout name.
   *
   * @return string
   *   Name of the Homebox Layout.
   */
  public function getName();

  /**
   * Sets the Homebox Layout name.
   *
   * @param string $name
   *   The Homebox Layout name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the Homebox Layout creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Homebox Layout.
   */
  public function getCreatedTime();

  /**
   * Sets the Homebox Layout creation timestamp.
   *
   * @param int $timestamp
   *   The Homebox Layout creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Homebox Layout published status indicator.
   *
   * Unpublished Homebox Layout are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Homebox Layout is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Homebox Layout.
   *
   * @param bool $published
   *   TRUE to set this Homebox Layout to published,
   *   FALSE to set it to unpublished.
   *
   * @return $this
   */
  public function setPublished($published);

  /**
   * Gets the Homebox Layout revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Homebox Layout revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return $this
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Homebox Layout revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Homebox Layout revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return $this
   */
  public function setRevisionUserId($uid);

}
