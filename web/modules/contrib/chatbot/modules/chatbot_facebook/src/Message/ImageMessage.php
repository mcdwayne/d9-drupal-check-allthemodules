<?php

namespace Drupal\chatbot_facebook\Message;

use Drupal\chatbot\Message\MessageInterface;

/**
 * Class ButtonMessage.
 *
 * @package Drupal\chatbot
 */
class ImageMessage implements MessageInterface {

  /**
   * The message Url.
   */
  protected $messageUrl;

  /**
   * ButtonMessage constructor.
   *
   * @param string $url
   *   The uro to use for this message.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the $buttons argument contains invalid objects.
   *
   * @todo: Add verification that the URL is actually a video.
   */
  public function __construct($url) {
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
    return [
      'attachment' => [
        'type' => 'image',
        'payload' => [
          'url' => $this->messageUrl,
        ],
      ],
    ];
  }

}
