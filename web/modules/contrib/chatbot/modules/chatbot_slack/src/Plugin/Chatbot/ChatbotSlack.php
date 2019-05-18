<?php

namespace Drupal\chatbot_slack\Plugin\Chatbot;

use Drupal\chatbot\Plugin\ChatbotPluginBase;
use Drupal\chatbot\Plugin\ChatbotPluginInterface;
use Drupal\chatbot\Conversation\ConversationFactoryInterface;
use Drupal\chatbot\Workflow\BotWorkflowInterface;
use Drupal\chatbot_slack\Service\SlackService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Slack Chatbot implementation.
 *
 * @ChatbotPlugin(
 *   id = "chatbot_slack",
 *   title = @Translation("Slack Chatbot")
 * )
 */
class ChatbotSlack extends ChatbotPluginBase implements ChatbotPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SlackService $service, ConversationFactoryInterface $conversationFactory, BotWorkflowInterface $workflow = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->service = $service;
    $service->configure($configuration);
    $this->conversationFactory = $conversationFactory;
    if ($workflow) {
      $this->setWorkflow($workflow);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Get all services from the container first.
    $service = $container->get('chatbot_slack.slack_service');
    $conversationFactory = $container->get('chatbot.conversation_factory');
    $workflow = $container->get('chatbot_slack.workflow');

    return new static($configuration, $plugin_id, $plugin_definition, $service, $conversationFactory, $workflow);
  }

  /**
   * {@inheritdoc}
   */
  public function challenge() {
    return $this->service->challenge();
  }

  /**
   * {@inheritdoc}
   */
  public function parsePostData(Request $request) {

    if ($request->getContentType() == 'json') {
      $body = json_decode($request->getContent());
      if ($body->type == 'url_verification') {
        return $this->challenge();
      }
      return $body;
    }
    return $request->request->all();
  }

}
