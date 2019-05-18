<?php

namespace Drupal\entity_collector\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Entity collection entities.
 *
 * @ingroup entity_collector
 */
interface EntityCollectionInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Entity collection name.
   *
   * @return string
   *   Name of the Entity collection.
   */
  public function getName();

  /**
   * Sets the Entity collection name.
   *
   * @param string $name
   *   The Entity collection name.
   *
   * @return \Drupal\entity_collector\Entity\EntityCollectionInterface
   *   The called Entity collection entity.
   */
  public function setName($name);

  /**
   * Gets the Entity collection creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Entity collection.
   */
  public function getCreatedTime();

  /**
   * Sets the Entity collection creation timestamp.
   *
   * @param int $timestamp
   *   The Entity collection creation timestamp.
   *
   * @return \Drupal\entity_collector\Entity\EntityCollectionInterface
   *   The called Entity collection entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Entity collection published status indicator.
   *
   * Unpublished Entity collection are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Entity collection is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Entity collection.
   *
   * @param bool $published
   *   TRUE to set this Entity collection to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\entity_collector\Entity\EntityCollectionInterface
   *   The called Entity collection entity.
   */
  public function setPublished($published);

  /**
   * Gets the Entity collection revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Entity collection revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\entity_collector\Entity\EntityCollectionInterface
   *   The called Entity collection entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Entity collection revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Entity collection revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\entity_collector\Entity\EntityCollectionInterface
   *   The called Entity collection entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Retrieve the list of participant id's.
   *
   * @return array
   *   Array containing participant id's.
   */
  public function getParticipantsIds();
}
