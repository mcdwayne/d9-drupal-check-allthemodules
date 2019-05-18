<?php

namespace Drupal\custom_messages\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Custom Message entities.
 *
 * @ingroup custom_messages
 */
interface CustomMessageInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Custom Message title.
   *
   * @return string
   *   Title of the Custom Message.
   */
  public function getTitle();

  /**
   * Sets the Custom Message title.
   *
   * @param string $title
   *   The Custom Message title.
   *
   * @return \Drupal\custom_messages\Entity\CustomMessageInterface
   *   The called Custom Message entity.
   */
  public function setTitle($title);

  /**
   * Gets the Custom Message creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Custom Message.
   */
  public function getCreatedTime();

  /**
   * Sets the Custom Message creation timestamp.
   *
   * @param int $timestamp
   *   The Custom Message creation timestamp.
   *
   * @return \Drupal\custom_messages\Entity\CustomMessageInterface
   *   The called Custom Message entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Custom Message published status indicator.
   *
   * Unpublished Custom Message are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Custom Message is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Custom Message.
   *
   * @param bool $published
   *   TRUE to set this Custom Message to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\custom_messages\Entity\CustomMessageInterface
   *   The called Custom Message entity.
   */
  public function setPublished($published);

}
