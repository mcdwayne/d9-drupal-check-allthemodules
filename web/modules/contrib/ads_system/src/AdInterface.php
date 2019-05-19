<?php

namespace Drupal\ads_system;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ad entities.
 *
 * @ingroup ads_system
 */
interface AdInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Ad type.
   *
   * @return string
   *   The Ad type.
   */
  public function getType();

  /**
   * Gets the Ad name.
   *
   * @return string
   *   Name of the Ad.
   */
  public function getName();

  /**
   * Sets the Ad name.
   *
   * @param string $name
   *   The Ad name.
   *
   * @return \Drupal\ads_system\AdInterface
   *   The called Ad entity.
   */
  public function setName($name);

  /**
   * Gets the Ad creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ad.
   */
  public function getCreatedTime();

  /**
   * Sets the Ad creation timestamp.
   *
   * @param int $timestamp
   *   The Ad creation timestamp.
   *
   * @return \Drupal\ads_system\AdInterface
   *   The called Ad entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Ad published status indicator.
   *
   * Unpublished Ad are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Ad is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Ad.
   *
   * @param bool $published
   *   TRUE to set this Ad to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\ads_system\AdInterface
   *   The called Ad entity.
   */
  public function setPublished($published);

}
