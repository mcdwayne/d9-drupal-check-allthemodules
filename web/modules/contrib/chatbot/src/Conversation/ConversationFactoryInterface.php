<?php

namespace Drupal\chatbot\Conversation;

/**
 * Interface ConversationFactoryInterface.
 *
 * @package Drupal\chatbot\Conversation
 */
interface ConversationFactoryInterface {

  /**
   * Load or instantiate a conversation, based on the sender's uid.
   *
   * @param string $uid
   *   The sender's user id.
   *
   * @return BotConversationInterface
   *   A conversation object.
   */
  public function getConversation($uid);

}
