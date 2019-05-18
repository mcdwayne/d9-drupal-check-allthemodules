<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeCustomFormatter;

/**
 * Formats multiple custom-formatted dates as a list.
 *
 * See the EntityListTrait for a description of list formatting features.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_datetime_custom_list",
 *   label       = @Translation("Formatter Suite - Custom date & time list"),
 *   weight      = 1000,
 *   field_types = {
 *     "datetime",
 *   }
 * )
 */
class DateTimeCustomListFormatter extends DateTimeCustomFormatter {
  use EntityListTrait;

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t("Format multi-value date & time fields as a list. Values may be formatted using a custom date/time format using PHP's format syntax, along with an optional time zone.");
  }

}
