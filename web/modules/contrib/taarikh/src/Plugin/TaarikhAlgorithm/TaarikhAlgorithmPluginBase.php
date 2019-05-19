<?php

namespace Drupal\taarikh\Plugin\TaarikhAlgorithm;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Plugin\PluginBase;
use Drupal\taarikh\TaarikhAlgorithmPluginInterface;
use Hussainweb\DateConverter\Value\DateInterface;

/**
 * The base class for all taarikh algorithm plugins.
 */
abstract class TaarikhAlgorithmPluginBase extends PluginBase implements TaarikhAlgorithmPluginInterface {

  /**
   * The decorated algorithm.
   *
   * @var \Hussainweb\DateConverter\Algorithm\AlgorithmInterface
   */
  protected $algorithm;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!empty($plugin_definition['algorithm_class']) && class_exists($plugin_definition['algorithm_class'])) {
      $this->algorithm = new $plugin_definition['algorithm_class'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fromJulianDay($julian_day) {
    return $this->algorithm->fromJulianDay($julian_day);
  }

  /**
   * {@inheritdoc}
   */
  public function toJulianDay(DateInterface $date) {
    return $this->algorithm->toJulianDay($date);
  }

  /**
   * {@inheritdoc}
   */
  public function isValidDate($month_day, $month, $year, &$errors) {
    return $this->algorithm->isValidDate($month_day, $month, $year, $errors);
  }

  /**
   * {@inheritdoc}
   */
  public function convertToDrupalDateTime(DateInterface $date) {
    return DrupalDateTime::createFromTimestamp($this->jdtounix($this->toJulianDay($date)));
  }

  /**
   * {@inheritdoc}
   */
  public function convertFromDrupalDateTime(DrupalDateTime $date) {
    $d = $date->format('d');
    $m = $date->format('m');
    $y = $date->format('Y');
    $jd = gregoriantojd($m, $d, $y);
    return $this->algorithm->fromJulianDay($jd);
  }

  /**
   * @inheritDoc
   */
  public function convertFromDateFormat($date, $format = NULL) {
    if (empty($format)) {
      $format = DateFormat::load('html_date')->getPattern();
    }

    $date = DrupalDateTime::createFromFormat($format, $date, NULL);
    return $this->convertFromDrupalDateTime($date);
  }

  /**
   * {@inheritdoc}
   */
  public function constructDateFromParts($month_day, $month, $year) {
    return $this->algorithm->constructDateValue($month_day, $month, $year);
  }

  /**
   * {@inheritdoc}
   */
  public function constructDateFromFormat($date, $format = NULL) {
    if (empty($format)) {
      $format = DateFormat::load('html_date')->getPattern();
    }

    // First, use createFromFormat to parse our date.
    $date = DrupalDateTime::createFromFormat($format, $date, NULL);
    // Now, construct our target date.
    return $this->constructDateFromParts(
      (int) $date->format('j'),
      (int) $date->format('n'),
      (int) $date->format('Y')
    );
  }

  /**
   * Write our own jdtounix implementation.
   *
   * The built-in jdtounix() function has issues with dates beyond the
   * UNIX epoch, and just returns false. This implementation provides a
   * simple and working substitute.
   *
   * See http://php.net/jdtounix for more details.
   *
   * @param int $jd
   *   The Julian Day
   *
   * @return int
   *   The timestamp corresponding to the Julian Day.
   */
  protected function jdtounix($jd) {
    return ($jd - 2440588) * 86400;
  }

}
