<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

/**
 * Plot Options Series.
 */
class PlotOptionsSeries implements \JsonSerializable {

  private $dataLabels;

  /**
   * Get Data Labels.
   *
   * @return mixed
   *   Data Labels.
   */
  public function getDataLabels() {
    return $this->dataLabels;
  }

  /**
   * Set Data Labels.
   *
   * @param mixed $dataLabels
   *   Data Labels.
   */
  public function setDataLabels($dataLabels) {
    $this->dataLabels = $dataLabels;
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
