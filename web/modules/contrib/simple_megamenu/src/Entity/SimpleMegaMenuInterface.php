<?php

namespace Drupal\simple_megamenu\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Simple mega menu entities.
 *
 * @ingroup simple_megamenu
 */
interface SimpleMegaMenuInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Simple mega menu type.
   *
   * @return string
   *   The Simple mega menu type.
   */
  public function getType();

  /**
   * Gets the Simple mega menu name.
   *
   * @return string
   *   Name of the Simple mega menu.
   */
  public function getName();

  /**
   * Sets the Simple mega menu name.
   *
   * @param string $name
   *   The Simple mega menu name.
   *
   * @return \Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface
   *   The called Simple mega menu entity.
   */
  public function setName($name);

  /**
   * Gets the Simple mega menu creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Simple mega menu.
   */
  public function getCreatedTime();

  /**
   * Sets the Simple mega menu creation timestamp.
   *
   * @param int $timestamp
   *   The Simple mega menu creation timestamp.
   *
   * @return \Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface
   *   The called Simple mega menu entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Simple mega menu published status indicator.
   *
   * Unpublished Simple mega menu are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Simple mega menu is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Simple mega menu.
   *
   * @param bool $published
   *   TRUE to set this entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface
   *   The called Simple mega menu entity.
   */
  public function setPublished($published);

  /**
   * Gets the Simple mega menu revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Simple mega menu revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface
   *   The called Simple mega menu entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Simple mega menu revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Simple mega menu revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface
   *   The called Simple mega menu entity.
   */
  public function setRevisionUserId($uid);

}
