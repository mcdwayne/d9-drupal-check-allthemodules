<?php

namespace Drupal\visualn_dataset\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining VisualN Data Set entities.
 *
 * @ingroup visualn_dataset
 */
interface VisualNDataSetInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the VisualN Data Set name.
   *
   * @return string
   *   Name of the VisualN Data Set.
   */
  public function getName();

  /**
   * Sets the VisualN Data Set name.
   *
   * @param string $name
   *   The VisualN Data Set name.
   *
   * @return \Drupal\visualn_dataset\Entity\VisualNDataSetInterface
   *   The called VisualN Data Set entity.
   */
  public function setName($name);

  /**
   * Gets the VisualN Data Set creation timestamp.
   *
   * @return int
   *   Creation timestamp of the VisualN Data Set.
   */
  public function getCreatedTime();

  /**
   * Sets the VisualN Data Set creation timestamp.
   *
   * @param int $timestamp
   *   The VisualN Data Set creation timestamp.
   *
   * @return \Drupal\visualn_dataset\Entity\VisualNDataSetInterface
   *   The called VisualN Data Set entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the VisualN Data Set published status indicator.
   *
   * Unpublished VisualN Data Set are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the VisualN Data Set is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a VisualN Data Set.
   *
   * @param bool $published
   *   TRUE to set this VisualN Data Set to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\visualn_dataset\Entity\VisualNDataSetInterface
   *   The called VisualN Data Set entity.
   */
  public function setPublished($published);

  /**
   * Gets the VisualN Data Set revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the VisualN Data Set revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\visualn_dataset\Entity\VisualNDataSetInterface
   *   The called VisualN Data Set entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the VisualN Data Set revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the VisualN Data Set revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\visualn_dataset\Entity\VisualNDataSetInterface
   *   The called VisualN Data Set entity.
   */
  public function setRevisionUserId($uid);

}
