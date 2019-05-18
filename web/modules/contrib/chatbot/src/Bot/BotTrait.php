<?php

namespace Drupal\chatbot\Bot;

use Drupal\chatbot\Workflow\BotWorkflowInterface;

/**
 * Trait BotTrait.
 *
 * @package Drupal\chatbot\Bot
 */
trait BotTrait {

  /**
   * The conversation factory.
   *
   * @var \Drupal\chatbot\Conversation\ConversationFactoryInterface
   */
  protected $conversationFactory;

  /**
   * The Workflow the bot will use.
   *
   * @var \Drupal\chatbot\Workflow\BotWorkflowInterface
   */
  protected $workflow;

  /**
   * The Service.
   *
   * @var \Drupal\chatbot\Service\ServiceInterface
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  public function process($data) {
    $incomingData = $this->service->translateRequest($data);
    // Iterate through received messages.
    foreach ($incomingData as $id => $incomingMessages) {
      foreach ($incomingMessages as $incomingMessage) {
        $conversation = $this->conversationFactory->getConversation($id);
        $response = $this->workflow->processConversation($conversation, $incomingMessage);
        $this->service->sendMessages($response, $id);
      }
    }
  }

  /**
   * Sets the bot's $workflow property.
   *
   * @param BotWorkflowInterface $workflow
   *   The Workflow to set.
   *
   * @todo: Set workflow in the conversation iterator of the process() method.
   */
  public function setWorkflow(BotWorkflowInterface $workflow) {
    $this->workflow = $workflow;
  }

  /**
   * Gets the bot's $workflow property.
   *
   * @return BotWorkflowInterface $workflow
   *   The Workflow property.
   */
  public function getWorkflow() {
    return $this->workflow;
  }

}
