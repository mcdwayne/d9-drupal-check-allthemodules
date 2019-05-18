<?php

namespace Drupal\chatbot_facebook\Plugin\Chatbot;

use Drupal\chatbot\Plugin\ChatbotPluginBase;
use Drupal\chatbot\Plugin\ChatbotPluginInterface;
use Drupal\chatbot\Conversation\ConversationFactoryInterface;
use Drupal\chatbot\Workflow\BotWorkflowInterface;
use Drupal\chatbot_facebook\Service\FacebookService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Facebook Chatbot implementation.
 *
 * @ChatbotPlugin(
 *   id = "chatbot_facebook",
 *   title = @Translation("Facebook Chatbot")
 * )
 */
class ChatbotFacebook extends ChatbotPluginBase implements ChatbotPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FacebookService $service, ConversationFactoryInterface $conversationFactory, BotWorkflowInterface $workflow = NULL) {
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
    $service = $container->get('chatbot_facebook.facebook_service');
    $conversationFactory = $container->get('chatbot.conversation_factory');
    $workflow = $container->get('chatbot_facebook.workflow');
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
    return $request->getContent();
  }

}
