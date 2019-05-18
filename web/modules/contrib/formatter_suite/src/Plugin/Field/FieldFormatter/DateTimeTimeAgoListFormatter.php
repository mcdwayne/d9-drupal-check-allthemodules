<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeTimeAgoFormatter;

/**
 * Formats multiple time ago date strings as a list.
 *
 * See the EntityListTrait for a description of list formatting features.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_datetime_time_ago_list",
 *   label       = @Translation("Formatter Suite - Time ago list"),
 *   weight      = 1002,
 *   field_types = {
 *     "datetime",
 *   }
 * )
 */
class DateTimeTimeAgoListFormatter extends DateTimeTimeAgoFormatter {
  use EntityListTrait;

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t("Format multi-value date & time fields as a list. Values are used to calculate a time period between the current date and the field's date. The time period is labeled as in the future or the past and presented with a selected granularity.");
  }

}
