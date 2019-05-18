<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Y Axis Label.
 */
class YaxisLabel implements \JsonSerializable {

  private $overflow = 'justify';

  /**
   * Set Overflow.
   *
   * @param mixed $overflow
   *   Overflow.
   */
  public function setOverflow($overflow) {
    $this->overflow = $overflow;
  }

  /**
   * Get Overflow.
   *
   * @return string
   *   Overflow.
   */
  public function getOverflow() {
    return $this->overflow;
  }

  /**
   * Json Serialize.
   *
   * @return array
   *   Json Serialize.
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
