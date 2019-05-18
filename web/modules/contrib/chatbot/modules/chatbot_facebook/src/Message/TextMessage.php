<?php

namespace Drupal\chatbot_facebook\Message;

use Drupal\chatbot\Message\MessageInterface;

class TextMessage implements MessageInterface {

  /**
   * The message text.
   */
  protected $messageText;

  /**
   * TextMessage constructor.
   *
   * @param string $text
   *   The text to use for this message.
   */
  public function __construct($text) {
    $this->messageText = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedMessage() {
    return [
      'text' => $this->messageText,
    ];
  }

}
