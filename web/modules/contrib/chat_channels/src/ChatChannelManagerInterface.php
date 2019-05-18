<?php
namespace Drupal\chat_channels;

use Drupal\chat_channels\Entity\ChatChannelInterface;
use Drupal\chat_channels\Entity\ChatChannelMemberInterface;
use Drupal\Core\Session\AccountInterface;

interface ChatChannelManagerInterface {
  /**
   * Load the latest messages.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelInterface $chat_channel
   *   chat_channel entity
   * @param int $limit
   *   Number of messages to load.
   * @param string $sort
   *   String containing the sort order. (ASC or DESC)
   * @return array $messages
   *   Array of message entities.
   */
  public function getLatestMessages(ChatChannelInterface $chat_channel, $limit = 20, $sort = 'DESC');

  /**
   * Get messages that fulfil the given conditions.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelInterface $chat_channel
   * @param bool $limit
   * @param string $sort
   *
   * @return \Drupal\Core\Entity\EntityInterface[] Array of message entities.
   * Array of message entities.
   */
  public function getMessages(ChatChannelInterface $chat_channel, $limit = FALSE, $sort = 'DESC');

  /**
   * Get a single member.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelInterface $chat_channel
   * @param \Drupal\core\Session\AccountInterface $user
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelMemberInterface
   */
  public function getMember(ChatChannelInterface $chat_channel, AccountInterface $user);

  /**
   * Let a user join a channel by creating a chat channel membership.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelInterface $chat_channel
   * @param \Drupal\Core\Session\AccountInterface $user
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelMemberInterface
   */
  public function joinChannel(ChatChannelInterface $chat_channel, AccountInterface $user);

  /**
   * Get new messages for a channel.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelMemberInterface $member
   * @param $count (optional)
   *   Boolean to return the count of the result.
   *
   * @return array $messages
   *   Array of message entities.
   */
  public function getNewMessages(ChatChannelMemberInterface $member, $count);

  /**
   * Get the count of the new messages for a member of a channel.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelMemberInterface $member
   *
   * @return array $messages
   *   Array of message entities.
   */
  public function getCountNewMessages(ChatChannelMemberInterface $member);

  /**
   * Get the count of the new messages as a render array for a member of a channel.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelMemberInterface $member
   *
   * @return array
   *   Render array for the indicator.
   */
  public function getNewMessageIndicator(ChatChannelMemberInterface $member);

  /**
   * Get the count of the new messages as a wrapped render array for a member of a channel.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelMemberInterface $member
   *
   * @param array $elements
   *   array containing element to merge into the render array.
   * @return array
   *   Render array for the indicator.
   */
  public function getWrappedNewMessageIndicator(ChatChannelMemberInterface $member, $elements);

  /**
   * Build render array for given chat channel message entities.getCountNewMessages
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelMessageInterface[] $messages
   * @return mixed
   */
  public function buildMessages($messages, $lastSeenMessage = NULL);

  /**
   * Get the first message of today.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelInterface $chat_channel
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelMessageInterface|mixed
   */
  public function getFirstMessageToday(ChatChannelInterface $chat_channel);


  /**
   * Get the first message of today.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelInterface $chat_channel
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *
   * @return \Drupal\chat_channels\Entity\ChatChannelMessageInterface|mixed
   */
  public function getLastSeenMessage(ChatChannelInterface $chat_channel, AccountInterface $user);

  /**
   * Get a chat channel object.
   *
   * @param $channelId
   *
   * @return mixed
   */
  public function getChannel($channelId);
}