<?php

namespace Drupal\chat_channels\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Chat channel member entities.
 *
 * @ingroup chat_channels
 */
interface ChatChannelMemberInterface extends ContentEntityInterface {

  /**
   * Gets the Chat channel member creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Chat channel member.
   */
  public function getCreatedTime();

  /**
   * Sets the Chat channel member creation timestamp.
   *
   * @param int $timestamp
   *   The Chat channel member creation timestamp.
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelMemberInterface
   *   The called Chat channel member entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Chat channel member published status indicator.
   *
   * Unpublished Chat channel member are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Chat channel member is published.
   */
  public function isActive();

  /**
   * Sets the published status of a Chat channel member.
   *
   * @param bool $active
   *   TRUE to set this Chat channel member to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelMemberInterface
   *   The called Chat channel member entity.
   */
  public function setActive($active);

  /**
   * Returns the uid of the member.
   *
   * @return int
   */
  public function getUserId();

  /**
   * Sets the Chat channel id.
   *
   * @return string
   *   The Chat channel id.
   */
  public function setChannelId($cid);

  /**
   * Gets the Chat channel id.
   *
   * @return string
   *   The Chat channel id.
   */
  public function getChannelId();

  /**
   * Gets the last seen message id.
   *
   * @return string
   *   The last seen message id.
   */
  public function getLastSeenMessageId();

  /**
   * Sets the last seen message id.
   *
   * @return string
   *   The last seen message id.
   */
  public function setLastSeenMessageId($LastSeenMessageId);
}
