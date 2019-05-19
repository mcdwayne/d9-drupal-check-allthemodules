<?php

namespace Drupal\twitter_entity\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Twitter entity entities.
 *
 * @ingroup twitter_entity
 */
interface TwitterEntityInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Twitter entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Twitter entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Twitter entity creation timestamp.
   *
   * @param int $timestamp
   *   The Twitter entity creation timestamp.
   *
   * @return \Drupal\twitter_entity\Entity\TwitterEntityInterface
   *   The called Twitter entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Twitter entity published status indicator.
   *
   * Unpublished Twitter entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Twitter entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Twitter entity.
   *
   * @param bool $published
   *   TRUE to set this Twitter entity to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\twitter_entity\Entity\TwitterEntityInterface
   *   The called Twitter entity entity.
   */
  public function setPublished($published);

}
