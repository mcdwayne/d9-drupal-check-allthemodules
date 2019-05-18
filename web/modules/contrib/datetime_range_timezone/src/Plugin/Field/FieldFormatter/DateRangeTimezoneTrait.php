<?php

namespace Drupal\datetime_range_timezone\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Common formatting methods.
 */
trait DateRangeTimezoneTrait {

  /**
   * Formats the date with the selected type.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date object.
   * @param string|null $timezone
   *   The timezone if we want to take it into account when printing.
   *
   * @return string
   *   The formatted date.
   */
  protected function formatDate(DrupalDateTime $date, $timezone = NULL) {
    $format_type = $this->getSetting('format_type');
    return $this->dateFormatter->format($date->getTimestamp(), $format_type, '', $timezone);
  }

  /**
   * Gets the date format options.
   *
   * Copied from core's DateTimeDefaultFormatter.
   *
   * @return array
   *   An array of options to be used in the form.
   */
  protected function getDateFormatOptions() {
    $time = new DrupalDateTime();
    $format_types = $this->dateFormatStorage->loadMultiple();
    $options = [];
    foreach ($format_types as $type => $type_info) {
      $format = $this->dateFormatter->format($time->getTimestamp(), $type);
      $options[$type] = sprintf('%s (%s)', $type_info->label(), $format);
    }

    return $options;
  }

}
