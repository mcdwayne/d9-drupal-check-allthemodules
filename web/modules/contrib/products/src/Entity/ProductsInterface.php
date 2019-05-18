<?php

namespace Drupal\products\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Products entities.
 *
 * @ingroup products
 */
interface ProductsInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Products name.
   *
   * @return string
   *   Name of the Products.
   */
  public function getName();

  /**
   * Sets the Products name.
   *
   * @param string $name
   *   The Products name.
   *
   * @return \Drupal\products\Entity\ProductsInterface
   *   The called Products entity.
   */
  public function setName($name);

  /**
   * Gets the Products creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Products.
   */
  public function getCreatedTime();

  /**
   * Sets the Products creation timestamp.
   *
   * @param int $timestamp
   *   The Products creation timestamp.
   *
   * @return \Drupal\products\Entity\ProductsInterface
   *   The called Products entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Products published status indicator.
   *
   * Unpublished Products are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Products is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Products.
   *
   * @param bool $published
   *   TRUE to set this Products to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\products\Entity\ProductsInterface
   *   The called Products entity.
   */
  public function setPublished($published);

  /**
   * Gets the Products revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Products revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\products\Entity\ProductsInterface
   *   The called Products entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Products revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Products revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\products\Entity\ProductsInterface
   *   The called Products entity.
   */
  public function setRevisionUserId($uid);

}
