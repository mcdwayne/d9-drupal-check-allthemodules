<?php

namespace Drupal\client_config_care\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Config blocker entity entities.
 *
 * @ingroup client_config_care
 */
interface ConfigBlockerEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Config blocker entity name.
   *
   * @return string
   *   Name of the Config blocker entity.
   */
  public function getName();

  /**
   * Sets the Config blocker entity name.
   *
   * @param string $name
   *   The Config blocker entity name.
   *
   * @return \Drupal\client_config_care\Entity\ConfigBlockerEntityInterface
   *   The called Config blocker entity entity.
   */
  public function setName($name);

  /**
   * Gets the Config blocker entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Config blocker entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Config blocker entity creation timestamp.
   *
   * @param int $timestamp
   *   The Config blocker entity creation timestamp.
   *
   * @return \Drupal\client_config_care\Entity\ConfigBlockerEntityInterface
   *   The called Config blocker entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Config blocker entity published status indicator.
   *
   * Unpublished Config blocker entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Config blocker entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Config blocker entity.
   *
   * @param bool $published
   *   TRUE to set this Config blocker entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\client_config_care\Entity\ConfigBlockerEntityInterface
   *   The called Config blocker entity entity.
   */
  public function setPublished($published);

  /**
   * Gets the Config blocker entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Config blocker entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\client_config_care\Entity\ConfigBlockerEntityInterface
   *   The called Config blocker entity entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Config blocker entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Config blocker entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\client_config_care\Entity\ConfigBlockerEntityInterface
   *   The called Config blocker entity entity.
   */
  public function setRevisionUserId($uid);

}
