<?php

namespace Drupal\global_content\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Global Content entities.
 *
 * @ingroup global_content
 */
interface GlobalContentInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Global Content name.
   *
   * @return string
   *   Name of the Global Content.
   */
  public function getName();

  /**
   * Sets the Global Content name.
   *
   * @param string $name
   *   The Global Content name.
   *
   * @return \Drupal\global_content\Entity\GlobalContentInterface
   *   The called Global Content entity.
   */
  public function setName($name);

  /**
   * Gets the Global Content creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Global Content.
   */
  public function getCreatedTime();

  /**
   * Sets the Global Content creation timestamp.
   *
   * @param int $timestamp
   *   The Global Content creation timestamp.
   *
   * @return \Drupal\global_content\Entity\GlobalContentInterface
   *   The called Global Content entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Global Content published status indicator.
   *
   * Unpublished Global Content are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Global Content is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Global Content.
   *
   * @param bool $published
   *   TRUE to set this Global Content to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\global_content\Entity\GlobalContentInterface
   *   The called Global Content entity.
   */
  public function setPublished($published);

  /**
   * Gets the Global Content revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Global Content revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\global_content\Entity\GlobalContentInterface
   *   The called Global Content entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Global Content revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Global Content revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\global_content\Entity\GlobalContentInterface
   *   The called Global Content entity.
   */
  public function setRevisionUserId($uid);

}
