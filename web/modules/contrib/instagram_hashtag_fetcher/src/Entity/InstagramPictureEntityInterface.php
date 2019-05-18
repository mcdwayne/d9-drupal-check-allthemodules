<?php

namespace Drupal\instagram_hashtag_fetcher\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Instagram Picture Entity entities.
 *
 * @ingroup instagram_pictures
 */
interface InstagramPictureEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Instagram Picture Entity name.
   *
   * @return string
   *   Name of the Instagram Picture Entity.
   */
  public function getName();

  /**
   * Sets the Instagram Picture Entity name.
   *
   * @param string $name
   *   The Instagram Picture Entity name.
   *
   * @return \Drupal\instagram_hashtag_fetcher\Entity\InstagramPictureEntityInterface
   *   The called Instagram Picture Entity entity.
   */
  public function setName($name);

  /**
   * Gets the Instagram Picture Entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Instagram Picture Entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Instagram Picture Entity creation timestamp.
   *
   * @param int $timestamp
   *   The Instagram Picture Entity creation timestamp.
   *
   * @return \Drupal\instagram_hashtag_fetcher\Entity\InstagramPictureEntityInterface
   *   The called Instagram Picture Entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Instagram Picture Entity published status indicator.
   *
   * Unpublished Instagram Picture Entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Instagram Picture Entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Instagram Picture Entity.
   *
   * @param bool $published
   *   TRUE to set this Instagram Picture Entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\instagram_hashtag_fetcher\Entity\InstagramPictureEntityInterface
   *   The called Instagram Picture Entity entity.
   */
  public function setPublished($published);

}
