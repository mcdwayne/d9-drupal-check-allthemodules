<?php

namespace Drupal\slack_rtm\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Slack RTM Messages.
 *
 * @ingroup slack_rtm
 */
interface SlackRtmMessageInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets the Slack RTM Message name.
   *
   * @return string
   *   Name of the Slack RTM Message.
   */
  public function getName();

  /**
   * Sets the Slack RTM Message name.
   *
   * @param string $name
   *   The Slack RTM Message name.
   *
   * @return \Drupal\slack_rtm\Entity\SlackRtmMessageInterface
   *   The called Slack RTM Message entity.
   */
  public function setName($name);

  /**
   * Gets the Slack RTM Message channel.
   *
   * @return string
   *   The Slack RTM Message channel.
   */
  public function getChannel();

  /**
   * Sets the Slack RTM Message channel.
   *
   * @param string $channel
   *   The Slack RTM Message channel string.
   *
   * @return \Drupal\slack_rtm\Entity\SlackRtmMessageInterface
   *   The called Slack RTM Message entity.
   */
  public function setChannel($channel);

  /**
   * Gets the Slack RTM Message.
   *
   * @return string
   *   The Slack RTM Message.
   */
  public function getMessage();

  /**
   * Sets the Slack RTM Message.
   *
   * @param string $channel
   *   The Slack RTM Message string.
   *
   * @return \Drupal\slack_rtm\Entity\SlackRtmMessageInterface
   *   The called Slack RTM Message entity.
   */
  public function setMessage($channel);

  /**
   * Gets the Slack RTM Message Permalink.
   *
   * @return string
   *   The Slack RTM Message Permalink.
   */
  public function getPermaLink();

  /**
   * Sets the Slack RTM Message Permalink.
   *
   * @param string $link
   *   The Slack RTM Message permalink string.
   *
   * @return \Drupal\slack_rtm\Entity\SlackRtmMessageInterface
   *   The called Slack RTM Message entity.
   */
  public function setPermaLink($link);
  /**
   * Gets the Slack RTM Message Author.
   *
   * @return string
   *   The Slack RTM Message Author.
   */
  public function getMessageAuthor();

  /**
   * Sets the Slack RTM Message Author.
   *
   * @param string $msg_author
   *   The Slack RTM Message Author string.
   *
   * @return \Drupal\slack_rtm\Entity\SlackRtmMessageInterface
   *   The called Slack RTM Message entity.
   */
  public function setMessageAuthor($msg_author);

  /**
   * Gets the Slack RTM Message creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Slack RTM Message.
   */
  public function getCreatedTime();

  /**
   * Sets the Slack RTM Message creation timestamp.
   *
   * @param int $timestamp
   *   The Slack RTM Message creation timestamp.
   *
   * @return \Drupal\slack_rtm\Entity\SlackRtmMessageInterface
   *   The called Slack RTM Message entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Slack RTM Message creation full ts.
   *
   * @return int
   *   Full creation timestamp of the Slack RTM Message.
   */
  public function getTid();

  /**
   * Sets the Slack RTM Message creation full tid.
   *
   * @param float $tid
   *   The Slack RTM Message creation full ts.
   *
   * @return \Drupal\slack_rtm\Entity\SlackRtmMessageInterface
   *   The called Slack RTM Message entity.
   */
  public function setTid($tid);

}
