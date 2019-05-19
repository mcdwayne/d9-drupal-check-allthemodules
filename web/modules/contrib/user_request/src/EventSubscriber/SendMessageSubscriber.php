<?php

namespace Drupal\user_request\EventSubscriber;

use Drupal\sender\SenderInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\user_request\Entity\RequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends configured message when a transtition happens.
 */
class SendMessageSubscriber implements EventSubscriberInterface {

  /**
   * Sender service.
   *
   * @var \Drupal\sender\SenderInterface
   */
  protected $sender;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'user_request.post_transition' => 'sendTransitionMessage',
    ];
  }

  /**
   * Sends the message configured for the transition.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function sendTransitionMessage(WorkflowTransitionEvent $event) {
    // Cannot send any message if the Sender service is unavailable.
    if (empty($this->sender)) {
      return;
    }

    // Gets the request from the event object.
    $request = $event->getEntity();
    $request_type = $request->getRequestType();

    // Checks if a message is configured for the transition.
    $transition = $event->getTransition();
    $transition_id = $transition->getId();
    $messages = $request_type->getMessages();
    if (!empty($messages['transitions'][$transition_id])) {
      // Sends the message to the sender and all request's recipients.
      $message_id = $messages['transitions'][$transition_id];
      $recipients = $request->getRecipients();
      $recipients[] = $request->getOwner();
      $data = [
        'user_request' => $request,
      ];
      $this->sender->send($message_id, $recipients, $data);
    }
  }

  public function sendNewRequestMessages(RequestInterface $entity) {
    // Sends configured messages if sender is enabled.
    if (!empty($this->sender)) {
      // The message settings are stored in the bundle entity.
      $request_type = $entity->getRequestType();
      $messages = $request_type->getMessages();

      // The data needed to render the messages is the same for all of them.
      $data = [
        'user_request' => $entity,
      ];

      // Sends message to the sender.
      if (!empty($messages['request_sent'])) {
        $message_id = $messages['request_sent'];
        $this->sender->send($message_id, $entity->getOwner(), $data);
      }

      // Sends message to request's recipients.
      if (!empty($messages['request_received'])) {
        $message_id = $messages['request_received'];
        $this->sender->send($message_id, $entity->getRecipients(), $data);
      }
    }
  }

  /**
   * Sets the Sender service.
   *
   * @param \Drupal\sender\SenderInterface $sender
   *   The Sender service.
   */
  public function setSender(SenderInterface $sender) {
    $this->sender = $sender;
  }

}
