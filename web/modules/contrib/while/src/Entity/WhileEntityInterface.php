<?php

namespace Drupal\white_label_entity\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining while entities.
 *
 * @ingroup while
 */
interface WhileEntityInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {
  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the While entity type.
   *
   * @return string
   *   The While entity type.
   */
  public function getType();

  /**
   * Gets the While entity name.
   *
   * @return string
   *   Name of the While entity.
   */
  public function getName();

  /**
   * Sets the While entity name.
   *
   * @param string $name
   *   The While entity name.
   *
   * @return \Drupal\white_label_entity\Entity\WhileEntityInterface
   *   The called While entity entity.
   */
  public function setName($name);

  /**
   * Gets the While entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the While entity.
   */
  public function getCreatedTime();

  /**
   * Sets the While entity creation timestamp.
   *
   * @param int $timestamp
   *   The While entity creation timestamp.
   *
   * @return \Drupal\white_label_entity\Entity\WhileEntityInterface
   *   The called While entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the While entity published status indicator.
   *
   * Unpublished While entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the While entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a While entity.
   *
   * @param bool $published
   *   TRUE to set this While entity to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\white_label_entity\Entity\WhileEntityInterface
   *   The called While entity entity.
   */
  public function setPublished($published);

  /**
   * Gets the While entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the While entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\white_label_entity\Entity\WhileEntityInterface
   *   The called While entity entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the While entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the While entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\white_label_entity\Entity\WhileEntityInterface
   *   The called While entity entity.
   */
  public function setRevisionUserId($uid);

}
