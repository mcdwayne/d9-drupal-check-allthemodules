<?php

namespace Drupal\charts_google\Settings\Google;

/**
 * Class GoogleOptions.
 *
 * @package Drupal\charts_google\Settings\Google
 */
class GoogleOptions implements \JsonSerializable {

  /**
   * For Material Charts, this option specifies the title.
   *
   * @var mixed
   */
  private $title;

  /**
   * For Material Charts, this option specifies the subtitle.
   *
   * @var mixed
   */
  private $subTitle;

  /**
   * Where to place the chart title, compared to the chart area.
   *
   * @var mixed
   */
  private $titlePosition;

  /**
   * Where to place the axis titles, compared to the chart area.
   *
   * @var mixed
   */
  private $axisTitlesPosition;

  /**
   * Chart Area.
   *
   * An array with members to configure the placement and size of the chart
   * area.
   *
   * @var mixed
   */
  private $chartArea;

  /**
   * Horizontal Axes.
   *
   * Specifies properties for individual horizontal axes, if the chart has
   * multiple horizontal axes.
   *
   * @var mixed
   */
  private $hAxes;

  /**
   * Vertical Axes.
   *
   * An array with members to configure various vertical axis elements.
   *
   * @var mixed
   */
  private $vAxes;

  /**
   * Colors.
   *
   * The colors to use for the chart elements. An array of strings, where each
   * element is an HTML color string.
   *
   * @var mixed
   */
  private $colors;

  /**
   * Legend.
   *
   * An array with members to configure various aspects of the legend. Or string
   * for the position of the legend.
   *
   * @var mixed
   */
  private $legend;

  /**
   * Width of the chart, in pixels.
   *
   * @var mixed
   */
  private $width;

  /**
   * Height of the chart, in pixels.
   *
   * @var mixed
   */
  private $height;

  /**
   * 3D chart option.
   *
   * @var mixed
   */
  private $is3D;

  /**
   * Stacking option.
   *
   * @var mixed
   */
  private $isStacked;

  /**
   * Gets the title of the Material Chart. Only Material Charts support titles.
   *
   * @return string
   *   Title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Sets the title of the Material Chart. Only Material Charts support titles.
   *
   * @param string $title
   *   Title.
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * Get Subtitle.
   *
   * Gets the subtitle of the Material Chart. Only Material Charts support
   * subtitle.
   *
   * @return string
   *   Subtitle.
   */
  public function getSubTitle() {
    return $this->subTitle;
  }

  /**
   * Set Subtitle.
   *
   * Sets the subtitle of the Material Chart. Only Material Charts support
   * subtitle.
   *
   * @param string $title
   *   SubTitle.
   */
  public function setSubTitle($title) {
    $this->subTitle = $title;
  }

  /**
   * Gets the position of chart title.
   *
   * @return string
   *   Title Position.
   */
  public function getTitlePosition() {
    return $this->titlePosition;
  }

  /**
   * Sets the position of chart title.
   *
   * Supported values:
   * - in: Draw the title inside the chart area.
   * - out: Draw the title outside the chart area.
   * - none: Omit the title.
   *
   * @param string $position
   *   Title Position.
   */
  public function setTitlePosition($position) {
    $this->titlePosition = $position;
  }

  /**
   * Gets the position of the axis titles.
   *
   * @return string
   *   Axis Titles Position.
   */
  public function getAxisTitlesPosition() {
    return $this->axisTitlesPosition;
  }

  /**
   * Sets the position of the axis titles.
   *
   * Supported values:
   * - in: Draw the axis titles inside the chart area.
   * - out: Draw the axis titles outside the chart area.
   * - none: Omit the axis titles.
   *
   * @param string $position
   *   Position.
   */
  public function setAxisTitlesPosition($position) {
    $this->axisTitlesPosition = $position;
  }

  /**
   * Gets the chartArea property.
   *
   * @return mixed
   *   Chart Area.
   */
  public function getChartArea() {
    return $this->chartArea;
  }

  /**
   * Sets the chartArea property.
   *
   * @param mixed $chartArea
   *   Chart Area.
   */
  public function setChartArea($chartArea) {
    $this->chartArea = $chartArea;
  }

  /**
   * Gets the horizontal axes.
   *
   * @return array
   *   Horizontal Axes.
   */
  public function getHorizontalAxes() {
    return $this->hAxes;
  }

  /**
   * Sets the horizontal axes.
   *
   * @param array $hAxes
   *   Horizontal axes.
   */
  public function setHorizontalAxes(array $hAxes = []) {
    $this->hAxes = $hAxes;
  }

  /**
   * Gets the vertical axes.
   *
   * @return array
   *   Vertical axes.
   */
  public function getVerticalAxes() {
    return $this->vAxes;
  }

  /**
   * Sets the vertical axes.
   *
   * @param array $vAxes
   *   Vertical axes.
   */
  public function setVerticalAxes(array $vAxes = []) {
    $this->vAxes = $vAxes;
  }

  /**
   * Get Colors.
   *
   * Gets the colors to use for the chart elements. An array of strings, where
   * each element is an HTML color string.
   *
   * @return array
   *   Colors.
   */
  public function getColors() {
    return $this->colors;
  }

  /**
   * Set Colors.
   *
   * Sets the colors to use for the chart elements. An array of strings, where
   * each element is an HTML color string.
   *
   * @param array $colors
   *   Colors.
   */
  public function setColors(array $colors = []) {
    $this->colors = $colors;
  }

  /**
   * Gets the Legend properties.
   *
   * @return mixed
   *   Legend.
   */
  public function getLegend() {
    return $this->legend;
  }

  /**
   * Sets the Legend properties.
   *
   * @param mixed $legend
   *   Legend.
   */
  public function setLegend($legend) {
    $this->legend = $legend;
  }

  /**
   * Gets a Legend property.
   *
   * @param mixed $key
   *   Property key.
   *
   * @return mixed
   *   Legend Property.
   */
  public function getLegendProperty($key) {
    return isset($this->legend[$key]) ? $this->legend[$key] : NULL;
  }

  /**
   * Sets a Legend property.
   *
   * @param mixed $key
   *   Property key.
   * @param mixed $value
   *   Property value.
   */
  public function setLegendProperty($key, $value) {
    $this->legend[$key] = $value;
  }

  /**
   * Gets the width of the chart.
   *
   * @return mixed
   *   Width.
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * Sets the width of the chart.
   *
   * @param mixed $width
   *   Width of the chart, in pixels.
   */
  public function setWidth($width) {
    $this->width = $width;
  }

  /**
   * Gets the height of the chart.
   *
   * @return mixed
   *   Height.
   */
  public function getHeight() {
    return $this->height;
  }

  /**
   * Sets the height of the chart.
   *
   * @param mixed $height
   *   Height of the chart, in pixels.
   */
  public function setHeight($height) {
    $this->height = $height;
  }

  /**
   * Gets three-dimensional chart option.
   *
   * @return mixed
   *   3D option.
   */
  public function getThreeDimensional() {
    return $this->is3D;
  }

  /**
   * Sets three-dimensional chart option.
   *
   * @param mixed $threeDimensional
   *   3D option.
   */
  public function setThreeDimensional($is3D) {
    $this->is3D = $is3D;
  }

  /**
   * Gets stacking chart option.
   *
   * @return mixed
   *   Stacking option.
   */
  public function getStacking() {
    return $this->isStacked;
  }

  /**
   * Sets stacking chart option.
   *
   * @param mixed $isStacked
   *   Stacking option.
   */
  public function setStacking($isStacked) {
    $this->isStacked = $isStacked;
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
