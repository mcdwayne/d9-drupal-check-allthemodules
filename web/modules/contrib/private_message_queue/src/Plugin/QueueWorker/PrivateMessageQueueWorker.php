<?php

namespace Drupal\private_message_queue\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\private_message\Entity\PrivateMessage;
use Drupal\private_message\Entity\PrivateMessageInterface;
use Drupal\private_message\Service\PrivateMessageServiceInterface;
use Drupal\private_message\Service\PrivateMessageThreadManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes queued private message threads.
 *
 * @QueueWorker(
 *   id = "private_message_queue",
 *   title = @Translation("Queue for creating private message threads."),
 *   cron = {"time" = 60}
 * )
 */
class PrivateMessageQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The private message service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageServiceInterface
   */
  private $privateMessage;

  /**
   * The private message thread manager service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageThreadManagerInterface
   */
  private $threadManager;

  /**
   * Creates a new \Drupal\private_message_queue\Plugin\QueueWorker\PrivateMessageThreadQueue.
   *
   * @param array $configuration
   *  A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\private_message\Service\PrivateMessageServiceInterface $private_message_service
   *   The private message service.
   * @param PrivateMessageThreadManagerInterface $thread_manager
   *   The private message thread manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateMessageServiceInterface $private_message_service, PrivateMessageThreadManagerInterface $thread_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->privateMessage = $private_message_service;
    $this->threadManager = $thread_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('private_message.service'),
      $container->get('private_message.thread_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (is_array($data['message']) && isset($data['message']['value']) && isset($data['message']['format'])) {
      $message = $this->createMessage($data['owner'], $data['message']['value'], $data['message']['format']);
    }
    else {
      $message = $this->createMessage($data['owner'], $data['message']);
    }

    $this->createThread($message, $data['recipients'], $data['owner']);
  }

  /**
   * Create a new private message.
   *
   * @param \Drupal\Core\Session\AccountInterface $owner
   *   The message owner.
   * @param string $message_body
   *   The message body.
   * @param string $text_format
   *   (optional) The text format. Defaults to 'basic_html'.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\private_message\Entity\PrivateMessage
   *   The created message.
   */
  private function createMessage(AccountInterface $owner, $message_body, $text_format = NULL) {
    $message = PrivateMessage::create([
      'owner'=> $owner->id(),
      'message' => [
        'value' => $message_body,
        'format' => $text_format ?: filter_default_format(),
      ],
    ]);
    $message->save();

    return $message;
  }

  /**
   * Create a private message thread.
   *
   * @param \Drupal\private_message\Entity\PrivateMessageInterface $message
   *   A message to add.
   * @param \Drupal\user\UserInterface[] $members
   *   The thread members.
   * @param \Drupal\Core\Session\AccountInterface $owner
   *   The message owner.
   */
  private function createThread(PrivateMessageInterface $message, array $members, AccountInterface $owner) {
    // Ensure that the message owner is added as a thread member.
    if (!in_array($owner, $members)) {
      $members[] = $owner;
    }

    $this->threadManager->saveThread($message, $members, [$owner]);
  }

}
