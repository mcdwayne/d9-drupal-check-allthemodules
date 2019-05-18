<?php

namespace Drupal\profile\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for profiles.
 */
interface ProfileInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Gets whether the profile is active.
   *
   * Unpublished profiles are only visible to their authors and administrators.
   *
   * @deprecated in Profile 1.0-rc4. Use ::isPublished instead.
   *
   * @return bool
   *   TRUE if the profile is active, FALSE otherwise.
   */
  public function isActive();

  /**
   * Sets whether the profile is active.
   *
   * @param bool $active
   *   Whether the profile is active.
   *
   * @deprecated in Profile 1.0-rc4. Use ::setPublished instead.
   *
   * @return $this
   */
  public function setActive($active);

  /**
   * Gets whether this is the user's default profile.
   *
   * A user can have a default profile of each type.
   *
   * @return bool
   *   TRUE if this is the user's default profile, FALSE otherwise.
   */
  public function isDefault();

  /**
   * Sets whether this is the user's default profile.
   *
   * @param bool $is_default
   *   Whether this is the user's default profile.
   *
   * @return $this
   */
  public function setDefault($is_default);

  /**
   * Gets a profile data value with the given key.
   *
   * Used to store arbitrary data for the profile.
   *
   * @param string $key
   *   The key.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The value.
   */
  public function getData($key, $default = NULL);

  /**
   * Sets a profile data value with the given key.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   */
  public function setData($key, $value);

  /**
   * Unsets a profile data value with the given key.
   *
   * @param string $key
   *   The key.
   *
   * @return $this
   */
  public function unsetData($key);

  /**
   * Gets the profile creation timestamp.
   *
   * @return int
   *   The profile creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the profile creation timestamp.
   *
   * @param int $timestamp
   *   The profile creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the profile revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the profile revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return $this
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the profile revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionAuthor();

  /**
   * Sets the profile revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return $this
   */
  public function setRevisionAuthorId($uid);

  /**
   * Populates the profile with field values from the other profile.
   *
   * Note: Only configurable fields are transferred. Base fields are skipped.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The other profile.
   *
   * @return $this
   */
  public function populateFromProfile(ProfileInterface $profile);

}
