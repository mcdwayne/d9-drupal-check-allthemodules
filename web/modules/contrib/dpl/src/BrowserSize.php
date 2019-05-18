<?php

namespace Drupal\dpl;

class BrowserSize {

  /**
   * @var int
   */
  protected $minWidth;

  /**
   * @var int
   */
  protected $maxWidth;

  /**
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $shortLabel;

  /**
   * BrowserSize constructor.
   *
   * @param int $min_width
   * @param int $max_width
   * @param string $label
   * @param string $short_label
   */
  public function __construct($min_width, $max_width, $label, $short_label) {
    assert(is_int($min_width));
    assert(is_int($max_width));
    assert(is_string($label));
    $this->minWidth = $min_width;
    $this->maxWidth = $max_width;
    $this->label = $label;
    $this->shortLabel = $short_label;
  }

  /**
   * @return int
   */
  public function getMinWidth() {
    return $this->minWidth;
  }

  /**
   * @return int
   */
  public function getMaxWidth() {
    return $this->maxWidth;
  }

  /**
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * @return string
   */
  public function getShortLabel() {
    return $this->shortLabel;
  }

}
