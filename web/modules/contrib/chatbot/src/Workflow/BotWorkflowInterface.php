<?php

namespace Drupal\chatbot\Workflow;

use Drupal\chatbot\Conversation\BotConversationInterface;
use Drupal\chatbot\Entity\Workflow;

/**
 * Interface BotWorkflowInterface.
 *
 * @package Drupal\chatbot\Workflow
 */
interface BotWorkflowInterface {

  /**
   * Populate workflow with steps representing the bot's side of conversation.
   *
   * @param \Traversable|array $steps
   *   Array or \Traversable implementation of BotWorkflowStepInterface objects.
   *
   * @return true
   *   Returns true.
   */
  public function setSteps($steps);

  /**
   * Configures the workflow based on Workflow entity.
   *
   * @param $entity
   *   Drupal\chatbot\Entity\Workflow
   *
   * @return true
   *   Returns true.
   */
  public function configure(Workflow $entity);

  /**
   * Process incoming data from the user.
   *
   * Given an incoming message from a user (array) and an int representing the
   * order of the Step to which they are responding, return an array
   * representing the next outgoing message which should be sent to them.
   *
   * @param BotConversationInterface $conversation
   *   The Conversation object to which the message pertains.
   * @param array $receivedMessage
   *   A PHP array of the incoming message, with keys of message_type and
   *   content.
   *
   * @return array MessageInterface
   *   Return value is an array of MessageInterface objects to be sent to the
   *   user.
   */
  public function processConversation(BotConversationInterface $conversation, array $receivedMessage);

}
