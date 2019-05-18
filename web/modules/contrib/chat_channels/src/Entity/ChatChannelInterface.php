<?php

namespace Drupal\chat_channels\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Chat channel entities.
 *
 * @ingroup chat_channels
 */
interface ChatChannelInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Chat channel type.
   *
   * @return string
   *   The Chat channel type.
   */
  public function getType();

  /**
   * Gets the Chat channel name.
   *
   * @return string
   *   Name of the Chat channel.
   */
  public function getName();

  /**
   * Sets the Chat channel name.
   *
   * @param string $name
   *   The Chat channel name.
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelInterface
   *   The called Chat channel entity.
   */
  public function setName($name);

  /**
   * Gets the Chat channel creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Chat channel.
   */
  public function getCreatedTime();

  /**
   * Sets the Chat channel creation timestamp.
   *
   * @param int $timestamp
   *   The Chat channel creation timestamp.
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelInterface
   *   The called Chat channel entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Chat channel active status indicator.
   *
   * Inactive Chat channel are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Chat channel is active.
   */
  public function isActive();

  /**
   * Sets the active status of a Chat channel.
   *
   * @param bool $active
   *   TRUE to set this Chat channel active, FALSE to set it to inactive.
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelInterface
   *   The called Chat channel entity.
   */
  public function setActive($active);


}
