<?php

namespace Drupal\views_timelinejs\TimelineJS;

/**
 * Defines a TimelineJS3 text object.
 */
class Text implements TextInterface {

  /**
   * The text object headline.
   *
   * @var string
   */
  protected $headline;

  /**
   * The text object text.
   *
   * Yes, this property's name is terribly ambiguous.
   *
   * @var string
   */
  protected $text;

  /**
   * Constructs a new Text object.
   *
   * @param string $headline
   *   The title of the text resource.
   * @param string $text
   *   The body of the text resource.
   */
  public function __construct($headline = '', $text = '') {
    $this->headline = $headline;
    $this->text = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function buildArray() {
    $text = [];
    if (!empty($this->headline)) {
      $text['headline'] = $this->headline;
    }
    if (!empty($this->text)) {
      $text['text'] = $this->text;
    }
    return $text;
  }

}
