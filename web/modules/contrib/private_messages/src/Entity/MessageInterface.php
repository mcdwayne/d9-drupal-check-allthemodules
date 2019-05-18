<?php

namespace Drupal\private_messages\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Message entities.
 *
 * @ingroup private_messages
 */
interface MessageInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Message name.
   *
   * @return string
   *   Name of the Message.
   */
  public function getSubject();

  /**
   * Sets the Message name.
   *
   * @param string $name
   *   The Message name.
   *
   * @return \Drupal\private_messages\Entity\MessageInterface
   *   The called Message entity.
   */
  public function setSubject($subject);

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
   * @return \Drupal\private_messages\Entity\MessageInterface
   *   The called Message entity.
   */
  public function setCreatedTime($timestamp);


  /**
   * Get related with dialog Entity.
   * @return \Drupal\private_messages\Entity\DialogInterface
   */
  public function getDialog();
}
