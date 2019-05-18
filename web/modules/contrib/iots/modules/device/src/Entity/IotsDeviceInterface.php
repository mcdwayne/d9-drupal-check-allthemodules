<?php

namespace Drupal\iots_device\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Device entities.
 *
 * @ingroup iots_device
 */
interface IotsDeviceInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Device name.
   *
   * @return string
   *   Name of the Device.
   */
  public function getName();

  /**
   * Sets the Device name.
   *
   * @param string $name
   *   The Device name.
   *
   * @return \Drupal\iots_device\Entity\IotsDeviceInterface
   *   The called Device entity.
   */
  public function setName($name);

  /**
   * Gets the Device creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Device.
   */
  public function getCreatedTime();

  /**
   * Sets the Device creation timestamp.
   *
   * @param int $timestamp
   *   The Device creation timestamp.
   *
   * @return \Drupal\iots_device\Entity\IotsDeviceInterface
   *   The called Device entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Device published status indicator.
   *
   * Unpublished Device are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Device is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Device.
   *
   * @param bool $published
   *   TRUE to set this Device to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\iots_device\Entity\IotsDeviceInterface
   *   The called Device entity.
   */
  public function setPublished($published);

  /**
   * Gets the Device revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Device revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\iots_device\Entity\IotsDeviceInterface
   *   The called Device entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Device revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Device revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\iots_device\Entity\IotsDeviceInterface
   *   The called Device entity.
   */
  public function setRevisionUserId($uid);

}
