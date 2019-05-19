<?php

namespace Drupal\views_timelinejs\TimelineJS;

/**
 * Defines a TimelineJS3 background object.
 */
class Background implements BackgroundInterface {

  /**
   * The background image url.
   *
   * @var string
   */
  protected $url;

  /**
   * The background color.
   *
   * A CSS color, in hexadecimal (e.g. #0f9bd1) or a valid CSS color keyword.
   *
   * @var string
   */
  protected $color;

  /**
   * Constructs a new Background object.
   *
   * @param string $url
   *   The background image URL.
   * @param string $color
   *   The background color.
   */
  public function __construct($url = '', $color = '') {
    $this->url = $url;
    $this->color = $color;
  }

  /**
   * {@inheritdoc}
   */
  public function buildArray() {
    $background = [];
    if (!empty($this->url)) {
      $background['url'] = $this->url;
    }
    if (!empty($this->color)) {
      $background['color'] = $this->color;
    }
    return $background;
  }

}
