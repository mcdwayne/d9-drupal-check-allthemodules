<?php

namespace Drupal\chatbot\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ChatbotPlugin type annotation object.
 *
 * ChatbotPlugin classes define chatbot types for Chatbot module.
 *
 * @see ChatbotPluginBase
 *
 * @ingroup chatbot
 *
 * @Annotation
 */
class ChatbotPlugin extends Plugin {

  /**
   * A unique identifier for the chatbot plugin.
   *
   * @var string
   */
  public $id;

}
