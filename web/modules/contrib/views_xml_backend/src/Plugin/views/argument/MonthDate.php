<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\argument\MonthDate.
 */

namespace Drupal\views_xml_backend\Plugin\views\argument;

use Drupal\views_xml_backend\Xpath;

/**
 * Argument handler for a month (MM).
 *
 * @ViewsArgument("views_xml_backend_date_month")
 */
class MonthDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $format = 'F';

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'm';

  /**
   * {@inheritdoc}
   */
  public function summaryName($data) {
    $month = str_pad($data->{$this->name_alias}, 2, '0', STR_PAD_LEFT);
    return format_date(strtotime("2005" . $month . "15" . " 00:00:00 UTC" ), 'custom', $this->format, 'UTC');
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    $month = str_pad($this->argument, 2, '0', STR_PAD_LEFT);
    return format_date(strtotime("2005" . $month . "15" . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * {@inheritdoc}
   */
  public function summaryArgument($data) {
    // Make sure the argument contains leading zeroes.
    return str_pad($data->{$this->base_alias}, 2, '0', STR_PAD_LEFT);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $xpath = $this->options['xpath_selector'];
    $value = Xpath::escapeXpathString(str_pad((int) $this->getValue(), 2, '0', STR_PAD_LEFT));
    $format = Xpath::escapeXpathString($this->argFormat);

    return "php:functionString('views_xml_backend_format_value', $xpath, $format) = $value";
  }

}
