<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Plot Options.
 */
class PlotOptions implements \JsonSerializable {

  private $series;

  /**
   * Get Plot Series.
   *
   * @return mixed
   *   Plot Series.
   */
  public function getPlotSeries() {
    return $this->series;
  }

  /**
   * Set Plot Series.
   *
   * @param mixed $series
   *   Plot Series.
   */
  public function setPlotSeries($series) {
    $this->series = $series;
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
