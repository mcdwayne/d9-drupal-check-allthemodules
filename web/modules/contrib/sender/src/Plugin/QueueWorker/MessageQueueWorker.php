<?php

namespace Drupal\sender\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\user\Entity\User;
use Drupal\sender\Entity\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes messages to be sent on cron runs.
 *
 * @QueueWorker(
 *   id = "sender_message_queue",
 *   title = @Translation("Sender messages queue"),
 *   cron = {"time" = 30}
 * )
 */
class MessageQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\sender\Plugin\SenderMethod\SenderMethodPluginManager
   */
  protected $pluginManager;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   Plugin manager to instatiate sender method plugins.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   String translation service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   A factory to instantiate a logger to log errors.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              PluginManagerInterface $plugin_manager,
                              TranslationInterface $string_translation,
                              LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->pluginManager = $plugin_manager;
    $this->stringTranslation = $string_translation;
    $this->logger = $logger_factory->get('sender');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin_manager = $container->get('plugin.manager.sender_method');
    $string_translation = $container->get('string_translation');
    $logger_factory = $container->get('logger.factory');

    return new static($configuration, $plugin_id, $plugin_definition,
      $plugin_manager, $string_translation, $logger_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Loads the message and recipient entitis.
    $message = Message::load($data['message_id']);
    $recipient = User::load($data['recipient_id']);

    // Instantiates a Sender method plugin.
    $method = $this->pluginManager->createInstance($data['method_id']);

    // Can not send the message if either the message or the recipient were
    // deleted. The method might also have been removed.
    if (!$message) {
      $t_args = [
        '%message_id' => $data['message_id'],
      ];
      $this->logger->error(t('Can not send message %message_id because it was deleted.', $t_args));
    }
    elseif (!$recipient) {
      $t_args = [
        '%message_id' => $data['message_id'],
        '%recipient_id' => $data['recipient_id'],
      ];
      $this->logger->error(t("Can not send message %message_id to %recipient_id because the recipient's account was deleted.", $t_args));
    }
    elseif (!$method) {
      $t_args = [
        '%message_id' => $data['message_id'],
        '%method_id' => $data['method_id'],
      ];
      $this->logger->error(t('Can not send message %message_id because the method %method_id is not available.'));
    }
    else {
      // Everything ok. Sends the message.
      $method->send($data['data'], $recipient, $message);
    }
  }

}
