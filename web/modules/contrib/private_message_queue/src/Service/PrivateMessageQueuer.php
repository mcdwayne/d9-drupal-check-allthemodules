<?php

namespace Drupal\private_message_queue\Service;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * A class for queuing requests to create private messages.
 */
class PrivateMessageQueuer {

  /**
   * The name of the queue.
   */
  const QUEUE_NAME = 'private_message_queue';

  /**
   * The queue plugin manager.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  private $queueFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * Creates a new instance of \Drupal\private_message_queue\Service\Thread\PrivateMessageThreadQueuer.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue plugin manager.
   */
  public function __construct(QueueFactory $queue_factory, AccountProxyInterface $current_user) {
    $this->queueFactory = $queue_factory;
    $this->currentUser = $current_user;
  }

  /**
   * Add an private message to the queue.
   *
   * @param \Drupal\user\UserInterface[] $recipients
   *   An array of recipients to include within the thread.
   * @param string|array $message
   *   The message body.
   * @param \Drupal\Core\Session\AccountInterface $owner
   *   The message owner.
   */
  public function queue(array $recipients, $message, AccountInterface $owner = NULL) {
    $queue = $this->queueFactory->get(self::QUEUE_NAME);
    $queue->createQueue();

    if (is_null($owner)) {
      $owner = $this->currentUser;
    }

    $queue->createItem(compact('recipients', 'message', 'owner'));
  }
  
}
