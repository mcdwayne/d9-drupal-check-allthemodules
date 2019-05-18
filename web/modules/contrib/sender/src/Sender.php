<?php

namespace Drupal\sender;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Config\ConfigFactory;
use Drupal\sender\Entity\Message;
use Drupal\sender\Plugin\SenderMethod\SenderMethodInterface;

/**
 * Sender service.
 */
class Sender implements SenderInterface {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\sender\Plugin\SenderMethod\SenderMethodPluginManager
   */
  protected $pluginManager;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $messageQueue;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   Plugin manager to instatiate sender method plugins.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   A factory to instantiate a logger to log errors.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler to be able to invoke hooks.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   A queue factory to get the messages queue from.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   A factory to get the module settings from.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(PluginManagerInterface $plugin_manager,
                              RendererInterface $renderer,
                              LoggerChannelFactoryInterface $logger_factory,
                              ModuleHandlerInterface $module_handler,
                              QueueFactory $queue_factory,
                              ConfigFactory $config_factory,
                              AccountInterface $current_user) {
    $this->pluginManager = $plugin_manager;
    $this->renderer = $renderer;
    $this->logger = $logger_factory->get('sender');
    $this->moduleHandler = $module_handler;
    $this->messageQueue = $queue_factory->get('sender_message_queue');
    $this->config = $config_factory->get('sender.settings');
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function send($message, $recipients = NULL, array $data = [], $method_ids = []) {
    // The $message parameter can be an object or an ID.
    if (!($message instanceof Message)) {
      $message = Message::load($message);
      if (!$message) {
        throw new \InvalidArgumentException(t('Parameter $message must match a message ID, %message given.', ['%message' => $message]));
      }
    }

    if (!isset($recipients)) {
      // Uses the current user if recipients was not provided.
      $recipients = $this->currentUser;
    }
    elseif (empty($recipients)) {
      // Does not need to send any message if there are no recipients.
      $this->logger->warning(t('Attempt to send message %message with no recipients.', ['%message' => $message->label()]));
      return;
    }

    // The $methods parameter can be a string or an array.
    if (!is_array($method_ids)) {
      $method_ids = [$method_ids];
    }
    $method_ids = $this->getMethodIds($method_ids);

    // Allows modules to change the list of methods.
    $this->moduleHandler->alter('sender_methods', $method_ids, $message);

    // The $recipients parameter can be a user account or an array.
    if (!is_array($recipients)) {
      $recipients = [$recipients];
    }

    // Allows modules to change the list of recipients.
    // This hook must be called after hook_sender_methods_alter() to receive
    // the final list of methods.
    $this->moduleHandler->alter('sender_recipients', $recipients, $message, $method_ids);

    // Gets the methods to send the message.
    if ($methods = $this->getMethods($method_ids)) {
      // Sends the message for each recipient using each method.
      foreach ($methods as $method) {
        foreach ($recipients as $recipient) {
          $this->sendSingle($message, $recipient, $data, $method);
        }
      }
    }
    else {
      // No method available.
      $this->logger->error('No methods available to send the message %message.', ['%message' => $message->label()]);
    }
  }

  /**
   * Sends a message to a single recipient.
   *
   * @param \Drupal\sender\Entity\Message $message
   *   The message object to be sent.
   * @param \Drupal\Core\Session\AccountInterface $recipient
   *   The account of the message's recipient.
   * @param array $data
   *   Data to be used for token replacements when building the message.
   * @param \Drupal\sender\Plugin\SenderMethod\SenderMethodInterface $method
   *   The plugin that will be used to actually send the message.
   *
   * @see \Drupal\Core\Utility\Token::replace()
   */
  protected function sendSingle(Message $message, AccountInterface $recipient, array $data, SenderMethodInterface $method) {
    // Builds the message.
    $render_array = $message->build($recipient, $data);

    // Includes the method in the render array to add theme suggestions.
    $render_array['#method'] = $method->id();

    // Renders the message.
    $rendered_message = $this->renderer->renderRoot($render_array);

    // Let the plugin send the message.
    $data = [
      'subject' => $render_array['#subject'],
      'body' => [
        'value' => $render_array['#body_text'],
        'format' => $render_array['#body_format'],
      ],
      'rendered' => $rendered_message,
    ];

    // Allows modules to change the data just before it is sent.
    $context = [
      'recipient' => $recipient,
      'method' => $method,
    ];
    $this->moduleHandler->alter('sender_message_data', $data, $message, $context);

    // Checks if the message should be sent now or enqueued.
    if ($this->config->get('queue_on')) {
      // Just enqueues the message to be sent at later time.
      $item = [
        'data' => $data,
        'recipient_id' => $recipient->id(),
        'message_id' => $message->id(),
        'method_id' => $method->id(),
      ];
      $this->messageQueue->createItem($item);
    }
    else {
      // Let the plugin send the message now.
      $method->send($data, $recipient, $message);
    }
  }

  /**
   * Completes the list of IDs with all plugins available if the list is empty.
   *
   * @param array $ids
   *   The list of sender method plugin IDs.
   *
   * @return array
   *   A list of plugin IDs. If $ids was empty, returns all plugins available.
   *   Otherwise, simply returns $ids.
   */
  protected function getMethodIds(array $ids = []) {
    // If $ids is empty gets all the available plugins.
    if (empty($ids)) {
      foreach ($this->pluginManager->getDefinitions() as $definition) {
        $ids[] = $definition['id'];
      }
    }
    return $ids;
  }

  /**
   * Loads the plugins corresponding to the passed IDs.
   *
   * @param array $ids
   *   A list of plugin IDs to load.
   *
   * @return array
   *   A list of sender method plugin objects.
   */
  protected function getMethods(array $ids = []) {
    // Loads plugins.
    $plugins = [];
    foreach ($ids as $plugin_id) {
      if ($plugin = $this->pluginManager->createInstance($plugin_id)) {
        $plugins[] = $plugin;
      }
      else {
        $this->logger->error(t('Plugin with ID %id does not exist.', ['%id' => $plugin_id]));
      }
    }
    return $plugins;
  }

}
