<?php

namespace Drupal\cloud\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Cloud config entities.
 *
 * @ingroup cloud
 */
interface CloudConfigInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Cloud config name.
   *
   * @return string
   *   Name of the Cloud config.
   */
  public function getName();

  /**
   * Sets the Cloud config name.
   *
   * @param string $name
   *   The Cloud config name.
   *
   * @return \Drupal\cloud\Entity\CloudConfigInterface
   *   The called Cloud config entity.
   */
  public function setName($name);

  /**
   * Gets the Cloud config creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Cloud config.
   */
  public function getCreatedTime();

  /**
   * Sets the Cloud config creation timestamp.
   *
   * @param int $timestamp
   *   The Cloud config creation timestamp.
   *
   * @return \Drupal\cloud\Entity\CloudConfigInterface
   *   The called Cloud config entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Cloud config published status indicator.
   *
   * Unpublished Cloud config are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Cloud config is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Cloud config.
   *
   * @param bool $published
   *   TRUE to set this Cloud config to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\cloud\Entity\CloudConfigInterface
   *   The called Cloud config entity.
   */
  public function setPublished($published);

  /**
   * Gets the Cloud config revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Cloud config revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\cloud\Entity\CloudConfigInterface
   *   The called Cloud config entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Cloud config revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Cloud config revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\cloud\Entity\CloudConfigInterface
   *   The called Cloud config entity.
   */
  public function setRevisionUserId($uid);

}
