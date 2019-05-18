<?php

namespace Drupal\chatbot_slack\Message;

/**
 * Base class for a button object.
 */
class PostbackButton extends ButtonBase {

  /**
   * Postback button constructor.
   *
   * @param string $text
   *   The button's title.
   * @param string $name
   *   The button's $name.
   */
  public function __construct($text, $name) {
    parent::__construct('button', $text, $name, $name);
  }

}
