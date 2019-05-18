<?php

namespace Drupal\iots_channel\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Iots Channel entities.
 *
 * @ingroup iots_channel
 */
interface IotsChannelInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Iots Channel name.
   *
   * @return string
   *   Name of the Iots Channel.
   */
  public function getName();

  /**
   * Sets the Iots Channel name.
   *
   * @param string $name
   *   The Iots Channel name.
   *
   * @return \Drupal\iots_channel\Entity\IotsChannelInterface
   *   The called Iots Channel entity.
   */
  public function setName($name);

  /**
   * Gets the Iots Channel creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Iots Channel.
   */
  public function getCreatedTime();

  /**
   * Sets the Iots Channel creation timestamp.
   *
   * @param int $timestamp
   *   The Iots Channel creation timestamp.
   *
   * @return \Drupal\iots_channel\Entity\IotsChannelInterface
   *   The called Iots Channel entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Iots Channel published status indicator.
   *
   * Unpublished Iots Channel are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Iots Channel is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Iots Channel.
   *
   * @param bool $published
   *   TRUE to set this Iots Channel to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\iots_channel\Entity\IotsChannelInterface
   *   The called Iots Channel entity.
   */
  public function setPublished($published);

}
