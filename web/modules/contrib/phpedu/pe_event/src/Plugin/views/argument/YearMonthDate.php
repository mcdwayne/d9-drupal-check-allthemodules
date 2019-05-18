<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument\YearMonthDate.
 */

namespace Drupal\pe_event\Plugin\views\argument;

use Drupal\datetime\Plugin\views\argument\Date;

/**
 * Argument handler for a year plus month (CCYYMM)
 *
 * @ViewsArgument("datetime_yearmonth")
 */
class YearMonthDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $format = 'F Y';

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Ym';

  /**
   * Provide a link to the next level of the view
   */
  public function summaryName($data) {
    $value = $data->{$this->name_alias};
    return format_date(strtotime($value . "15" . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    return format_date(strtotime($this->argument . "15" . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }
}
