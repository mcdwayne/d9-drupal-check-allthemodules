<?php

namespace Drupal\chatbot_facebook\Message;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Drupal\chatbot\Message\MessageInterface;

/**
 * Class ButtonMessage.
 *
 * @package Drupal\chatbot
 */
class ButtonMessage implements MessageInterface {

  /**
   * The message text.
   */
  protected $messageText;

  /**
   * An array of buttons.
   */
  protected $messageButtons = array();

  /**
   * ButtonMessage constructor.
   *
   * @param string $text
   *   The text to use for this message.
   * @param array $buttons
   *   an array of objects extending \Drupal\chatbot\Message\Facebook\ButtonBase.
   *
   * @throws InvalidArgumentException
   *   Thrown if the $buttons argument contains invalid objects.
   */
  public function __construct($text, $buttons = array()) {
    $this->messageText = $text;
    foreach ($buttons as $button) {
      if (!($button instanceof ButtonBase)) {
        throw new InvalidArgumentException("Buttons supplied to the ButtonMessage Constuctor must be an instance of ButtonBase.");
      }
      $this->messageButtons[] = $button->toArray();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedMessage() {
    return [
      'attachment' => [
        'type' => 'template',
        'payload' => [
          'template_type' => 'button',
          'text' => $this->messageText,
          'buttons' => $this->messageButtons
        ],
      ],
    ];
  }

}
