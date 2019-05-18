<?php
/**
 * @file
 * Contains Drupal\chatbot_slack\Message\ImageMessage.
 */

namespace Drupal\chatbot_slack\Message;

use Drupal\chatbot\Message\MessageInterface;

/**
 * Class ImageMessage.
 *
 * @package Drupal\chatbot
 */
class ImageMessage implements MessageInterface {

  /**
   * The message Url.
   */
  protected $messageUrl;

  /**
   * The message text.
   */
  protected $messageText;

  /**
   * ButtonMessage constructor.
   *
   * @param string $text
   *   The text to use for this message.
   *
   * @param string $url
   *   The uro to use for this message.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the $buttons argument contains invalid objects.
   *
   * @todo: Add verification that the URL is actually a video.
   */
  public function __construct($text, $url) {
    $this->messageText = $text;
    if (filter_var($url, FILTER_VALIDATE_URL)) {
      $this->messageUrl = $url;
    }
    else {
      throw new \InvalidArgumentException("Invalid URL passed to ImageMessage constructor.");
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedMessage() {
    $image = new \stdClass();
    $image->text = $this->messageText;
    $image->attachments = [$this->getAttachment()];
    return $image;
  }

  private function getAttachment() {
    $attachment = new \stdClass();
    $attachment->fallback = 'This message is invalid';
    $attachment->color = "#FFDDFF";
    $attachment->image_url = $this->messageUrl;
    return $attachment;
  }

}
