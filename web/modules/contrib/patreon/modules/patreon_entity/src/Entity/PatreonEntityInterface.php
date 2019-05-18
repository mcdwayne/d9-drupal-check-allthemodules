<?php

namespace Drupal\patreon_entity\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Patreon entity entities.
 *
 * @ingroup patreon_entity
 */
interface PatreonEntityInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Patreon entity name.
   *
   * @return string
   *   Name of the Patreon entity.
   */
  public function getName();

  /**
   * Sets the Patreon entity name.
   *
   * @param string $name
   *   The Patreon entity name.
   *
   * @return \Drupal\patreon_entity\Entity\PatreonEntityInterface
   *   The called Patreon entity entity.
   */
  public function setName($name);

  /**
   * Gets the Patreon entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Patreon entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Patreon entity creation timestamp.
   *
   * @param int $timestamp
   *   The Patreon entity creation timestamp.
   *
   * @return \Drupal\patreon_entity\Entity\PatreonEntityInterface
   *   The called Patreon entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Patreon entity published status indicator.
   *
   * Unpublished Patreon entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Patreon entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Patreon entity.
   *
   * @param bool $published
   *   TRUE to set this Patreon entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\patreon_entity\Entity\PatreonEntityInterface
   *   The called Patreon entity entity.
   */
  public function setPublished($published);

  /**
   * Gets the Patreon entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Patreon entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\patreon_entity\Entity\PatreonEntityInterface
   *   The called Patreon entity entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Patreon entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Patreon entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\patreon_entity\Entity\PatreonEntityInterface
   *   The called Patreon entity entity.
   */
  public function setRevisionUserId($uid);

}
