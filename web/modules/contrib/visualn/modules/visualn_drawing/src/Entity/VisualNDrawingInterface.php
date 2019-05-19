<?php

namespace Drupal\visualn_drawing\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\visualn\WindowParametersInterface;

/**
 * Provides an interface for defining VisualN Drawing entities.
 *
 * @ingroup visualn_drawing
 */
interface VisualNDrawingInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface, WindowParametersInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the VisualN Drawing name.
   *
   * @return string
   *   Name of the VisualN Drawing.
   */
  public function getName();

  /**
   * Sets the VisualN Drawing name.
   *
   * @param string $name
   *   The VisualN Drawing name.
   *
   * @return \Drupal\visualn_drawing\Entity\VisualNDrawingInterface
   *   The called VisualN Drawing entity.
   */
  public function setName($name);

  /**
   * Gets the VisualN Drawing creation timestamp.
   *
   * @return int
   *   Creation timestamp of the VisualN Drawing.
   */
  public function getCreatedTime();

  /**
   * Sets the VisualN Drawing creation timestamp.
   *
   * @param int $timestamp
   *   The VisualN Drawing creation timestamp.
   *
   * @return \Drupal\visualn_drawing\Entity\VisualNDrawingInterface
   *   The called VisualN Drawing entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the VisualN Drawing published status indicator.
   *
   * Unpublished VisualN Drawing are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the VisualN Drawing is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a VisualN Drawing.
   *
   * @param bool $published
   *   TRUE to set this VisualN Drawing to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\visualn_drawing\Entity\VisualNDrawingInterface
   *   The called VisualN Drawing entity.
   */
  public function setPublished($published);

  /**
   * Gets the VisualN Drawing revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the VisualN Drawing revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\visualn_drawing\Entity\VisualNDrawingInterface
   *   The called VisualN Drawing entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the VisualN Drawing revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the VisualN Drawing revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\visualn_drawing\Entity\VisualNDrawingInterface
   *   The called VisualN Drawing entity.
   */
  public function setRevisionUserId($uid);

}
