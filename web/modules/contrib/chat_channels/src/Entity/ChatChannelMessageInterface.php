<?php

namespace Drupal\chat_channels\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Chat channel message entities.
 *
 * @ingroup chat_channels
 */
interface ChatChannelMessageInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Chat channel message name.
   *
   * @return string
   *   Name of the Chat channel message.
   */
  public function getName();

  /**
   * Sets the Chat channel message name.
   *
   * @param string $name
   *   The Chat channel message name.
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelMessageInterface
   *   The called Chat channel message entity.
   */
  public function setName($name);

  /**
   * Gets the Chat channel message creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Chat channel message.
   */
  public function getCreatedTime();

  /**
   * Sets the Chat channel message creation timestamp.
   *
   * @param int $timestamp
   *   The Chat channel message creation timestamp.
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelMessageInterface
   *   The called Chat channel message entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Chat channel message published status indicator.
   *
   * Unpublished Chat channel message are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Chat channel message is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Chat channel message.
   *
   * @param bool $published
   *   TRUE to set this Chat channel message to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelMessageInterface
   *   The called Chat channel message entity.
   */
  public function setPublished($published);

  /**
   * Gets the Chat channel message content.
   *
   * @return string
   *   Content of the Chat channel message.
   */
  public function getMessage();

  /**
   * Gets the Chat channel message content.
   *
   * @return string
   *   Content of the Chat channel message.
   */
  public function setMessage($message);

}
