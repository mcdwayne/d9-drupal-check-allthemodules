<?php

namespace Drupal\friends\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Activity entities.
 *
 * @ingroup friends
 */
interface FriendsInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Activity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Activity.
   */
  public function getCreatedTime();

  /**
   * Sets the Activity creation timestamp.
   *
   * @param int $timestamp
   *   The Activity creation timestamp.
   *
   * @return \Drupal\friends\FriendsInterface
   *   The called Activity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Activity published status indicator.
   *
   * Unpublished Activity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Activity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Activity.
   *
   * @param bool $published
   *   TRUE to set this Activity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\friends\FriendsInterface
   *   The called Activity entity.
   */
  public function setPublished($published);

  /**
   * Get recipient user entity.
   *
   * @return \Drupal\user\UserInterface
   *   Returns the user object of the recipient user.
   */
  public function getRecipient();

  /**
   * Get recipient user id.
   *
   * @return int
   *   Returns the user id of the recipient user
   */
  public function getRecipientId();

  /**
   * Get the user entity of the user who last updated the entity.
   *
   * @return \Drupal\user\UserInterface
   *   Returns the user object of the updater.
   */
  public function getUpdater();

  /**
   * Get the user id of the user who last updated the entity.
   *
   * @return int
   *   Returns the user id of the updater.
   */
  public function getUpdaterId();

  /**
   * Returns the friend request status.
   *
   * @param bool $human_readable
   *   Whether to return the machine_name of the status or the human readable
   *   one.
   *
   * @return string
   *   The status.
   */
  public function getStatus(bool $human_readable = FALSE);

  /**
   * Returns the friend request type.
   *
   * @param bool $human_readable
   *   Whether to return the machine_name of the type or the human readable
   *   one.
   *
   * @return string
   *   The type.
   */
  public function getType(bool $human_readable = FALSE);

  /**
   * Returns url for responding to the friend request with the given status.
   *
   * @param string $status
   *   The maching_name of the status.
   * @param array $options
   *   An array of options, see \Drupal\Core\Url::fromUri() for details.
   *
   * @return \Drupal\Core\Url
   *   A Url object that would change the value of this entity to the given
   *   status.
   */
  public function getStatusUrl(string $status, array $options = []);

}
