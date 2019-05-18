<?php

namespace Drupal\chatbot_facebook\Message;

/**
 * The web_url type button.
 */
class UrlButton extends ButtonBase {

  /**
   * The button's URL.
   */
  protected $url;

  /**
   * Url button constructor.
   *
   * @param string $type
   *   The button type.
   * @param string $title
   *   The button's title.
   * @param string $url
   *   The url the button should link to.
   */
  public function __construct($title, $url) {
    parent::__construct('web_url', $title);
    $this->url = $url;
  }

}
