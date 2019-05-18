<?php

namespace Drupal\chatbot\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Message entities.
 *
 * @ingroup chatbot
 */
interface MessageInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Message type.
   *
   * @return string
   *   The Message type.
   */
  public function getType();

  /**
   * Gets the Message title.
   *
   * @return string
   *   Name of the Message.
   */
  public function getTitle();

  /**
   * Sets the Message title.
   *
   * @param string $title
   *   The Message title.
   *
   * @return \Drupal\chatbot\Entity\MessageInterface
   *   The called Message entity.
   */
  public function setTitle($title);

  /**
   * Gets the Message creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Message.
   */
  public function getCreatedTime();

  /**
   * Sets the Message creation timestamp.
   *
   * @param int $timestamp
   *   The Message creation timestamp.
   *
   * @return \Drupal\chatbot\Entity\MessageInterface
   *   The called Message entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Message published status indicator.
   *
   * Unpublished Message are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Message is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Message.
   *
   * @param bool $published
   *   TRUE to set this Message to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\chatbot\Entity\MessageInterface
   *   The called Message entity.
   */
  public function setPublished($published);

}
