<?php

namespace Drupal\chatbot_slack\Message;

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
  protected $callback_id;

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
  public function __construct($text, $callback_id, $buttons = []) {
    $this->messageText = $text;
    $this->callback_id = $callback_id;
    foreach ($buttons as $button) {
      if (!($button instanceof ButtonBase)) {
        throw new InvalidArgumentException("Buttons supplied to the ButtonMessage Constuctor must be an instance of ButtonBase.");
      }
      $this->messageButtons[] = $button->toObject();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedMessage() {
    $button = new \stdClass();
    $button->attachments = [$this->getAttachment()];
    return $button;
  }

  private function getAttachment() {
    $attachment = new \stdClass();
    $attachment->fallback = 'This message is invalid';
    $attachment->callback_id = $this->callback_id;
    $attachment->text = $this->messageText;
    $attachment->actions = $this->messageButtons;
    return $attachment;
  }

}
