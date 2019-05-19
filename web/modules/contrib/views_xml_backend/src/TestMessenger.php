<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\TestMessenger.
 */

namespace Drupal\views_xml_backend;

/**
 * The messenger used for tests.
 */
class TestMessenger implements MessengerInterface {

  /**
   * A list of messages received.
   *
   * @var array
   */
  protected $messages = [];

  /**
   * {@inheritdoc}
   */
  public function setMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    $this->messages[$type][] = (string) $message;
  }

  /**
   * Returns the messages that were sent keyed by message type.
   *
   * @return array
   *   The list of messages.
   */
  public function getMessages() {
    return $this->messages;
  }

}
