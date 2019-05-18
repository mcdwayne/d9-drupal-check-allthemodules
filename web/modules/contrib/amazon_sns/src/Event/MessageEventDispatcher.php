<?php

namespace Drupal\amazon_sns\Event;

use Aws\Sns\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches SNS message events.
 */
class MessageEventDispatcher implements ContainerInjectionInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher')
    );
  }

  /**
   * Construct a new Amazon SNS event dispatcher.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The Symfony event dispatcher used to send events.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Dispatch a message depending on the SNS message type.
   *
   * @param \Aws\Sns\Message $message
   *   The validated message from SNS.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the Type header is missing from the SNS message.
   */
  public function dispatch(Message $message) {
    $event = new SnsMessageEvent($message);
    switch ($message['Type']) {
      case 'SubscriptionConfirmation':
        $this->eventDispatcher->dispatch(SnsEvents::SUBSCRIPTION_CONFIRMATION, $event);
        break;

      case 'Notification':
        $this->eventDispatcher->dispatch(SnsEvents::NOTIFICATION, $event);
        break;

      case 'UnsubscribeConfirmation':
        $this->eventDispatcher->dispatch(SnsEvents::UNSUBSCRIBE_CONFIRMATION, $event);
        break;

      default:
        throw new \InvalidArgumentException(sprintf('Message type %s is not a valid message type', $message['Type']));
    }
  }

}
