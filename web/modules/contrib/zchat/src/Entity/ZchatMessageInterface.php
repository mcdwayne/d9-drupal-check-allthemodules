<?php

namespace Drupal\zchat\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Zchat Message entities.
 *
 * @ingroup zchat
 */
interface ZchatMessageInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Zchat Message name.
   *
   * @return string
   *   Name of the Zchat Message.
   */
  public function getName();

  /**
   * Sets the Zchat Message name.
   *
   * @param string $name
   *   The Zchat Message name.
   *
   * @return \Drupal\zchat\Entity\ZchatMessageInterface
   *   The called Zchat Message entity.
   */
  public function setName($name);

  /**
   * Gets the Zchat Message creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Zchat Message.
   */
  public function getCreatedTime();

  /**
   * Sets the Zchat Message creation timestamp.
   *
   * @param int $timestamp
   *   The Zchat Message creation timestamp.
   *
   * @return \Drupal\zchat\Entity\ZchatMessageInterface
   *   The called Zchat Message entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Zchat Message published status indicator.
   *
   * Unpublished Zchat Message are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Zchat Message is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Zchat Message.
   *
   * @param bool $published
   *   TRUE to set this Zchat Message to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\zchat\Entity\ZchatMessageInterface
   *   The called Zchat Message entity.
   */
  public function setPublished($published);

}
