<?php

namespace Drupal\orders\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Orders entities.
 *
 * @ingroup orders
 */
interface OrdersInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Orders name.
   *
   * @return string
   *   Name of the Orders.
   */
  public function getName();

  /**
   * Sets the Orders name.
   *
   * @param string $name
   *   The Orders name.
   *
   * @return \Drupal\orders\Entity\OrdersInterface
   *   The called Orders entity.
   */
  public function setName($name);

  /**
   * Gets the Orders creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Orders.
   */
  public function getCreatedTime();

  /**
   * Sets the Orders creation timestamp.
   *
   * @param int $timestamp
   *   The Orders creation timestamp.
   *
   * @return \Drupal\orders\Entity\OrdersInterface
   *   The called Orders entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Orders published status indicator.
   *
   * Unpublished Orders are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Orders is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Orders.
   *
   * @param bool $published
   *   TRUE to set this Orders to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\orders\Entity\OrdersInterface
   *   The called Orders entity.
   */
  public function setPublished($published);

  /**
   * Gets the Orders revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Orders revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\orders\Entity\OrdersInterface
   *   The called Orders entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Orders revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Orders revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\orders\Entity\OrdersInterface
   *   The called Orders entity.
   */
  public function setRevisionUserId($uid);

}
