<?php

namespace Drupal\chatbot_facebook\Message;

use Drupal\chatbot\Message\MessageInterface;

/**
 * Class FacebookGenericMessage.
 *
 * @package Drupal\chatbot
 */
class FacebookGenericMessage implements MessageInterface {

  /**
   * The elements storage.
   */
  protected $elements;

  /**
   * Constructs a new FacebookGenericMessage.
   *
   * @param array $elements
   *   The message elements.
   */
  public function __construct(array $elements) {
    $this->elements = $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedMessage() {
    return [
      'attachment' => [
        'type' => 'template',
        'payload' => [
          'template_type' => 'generic',
          'elements' => $this->elements,
        ],
      ],
    ];
  }

}
