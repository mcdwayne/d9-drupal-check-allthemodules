<?php

namespace Drupal\amazon_sns\Event;

use Aws\Sns\Message;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event class wrapping an SNS message.
 *
 * As the Symfony Event system doesn't have an 'EventInterface', we have to
 * extend the class to wrap our SNS event.
 */
class SnsMessageEvent extends Event {

  /**
   * The notification from SNS.
   *
   * @var \Aws\Sns\Message
   */
  protected $message;

  /**
   * Construct a new SnsMessageEvent.
   *
   * @param \Aws\Sns\Message $message
   *   The notification from SNS.
   */
  public function __construct(Message $message) {
    $this->message = $message;
  }

  /**
   * Return the SNS message.
   *
   * @return \Aws\Sns\Message
   *   The notification from SNS.
   */
  public function getMessage() {
    return $this->message;
  }

}
