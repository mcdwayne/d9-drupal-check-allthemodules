<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\argument\DayDate.
 */

namespace Drupal\views_xml_backend\Plugin\views\argument;

use Drupal\views_xml_backend\Xpath;

/**
 * Argument handler for a day (DD).
 *
 * @ViewsArgument("views_xml_backend_date_day")
 */
class DayDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $format = 'j';

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'd';

  /**
   * {@inheritdoc}
   */
  public function summaryName($data) {
    $day = str_pad($data->{$this->name_alias}, 2, '0', STR_PAD_LEFT);
    // strtotime respects server timezone, so we need to set the time fixed as utc time
    return format_date(strtotime("2005" . "05" . $day . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    $day = str_pad($this->argument, 2, '0', STR_PAD_LEFT);
    return format_date(strtotime("2005" . "05" . $day . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
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
