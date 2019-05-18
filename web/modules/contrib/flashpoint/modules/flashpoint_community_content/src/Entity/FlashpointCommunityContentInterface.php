<?php

namespace Drupal\flashpoint_community_content\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Flashpoint community content entities.
 *
 * @ingroup flashpoint_community_content
 */
interface FlashpointCommunityContentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Flashpoint community content name.
   *
   * @return string
   *   Name of the Flashpoint community content.
   */
  public function getName();

  /**
   * Sets the Flashpoint community content name.
   *
   * @param string $name
   *   The Flashpoint community content name.
   *
   * @return \Drupal\flashpoint_community_content\Entity\FlashpointCommunityContentInterface
   *   The called Flashpoint community content entity.
   */
  public function setName($name);

  /**
   * Gets the Flashpoint community content creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Flashpoint community content.
   */
  public function getCreatedTime();

  /**
   * Sets the Flashpoint community content creation timestamp.
   *
   * @param int $timestamp
   *   The Flashpoint community content creation timestamp.
   *
   * @return \Drupal\flashpoint_community_content\Entity\FlashpointCommunityContentInterface
   *   The called Flashpoint community content entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Flashpoint community content published status indicator.
   *
   * Unpublished Flashpoint community content are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Flashpoint community content is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Flashpoint community content.
   *
   * @param bool $published
   *   TRUE to set this Flashpoint community content to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\flashpoint_community_content\Entity\FlashpointCommunityContentInterface
   *   The called Flashpoint community content entity.
   */
  public function setPublished($published);

}
