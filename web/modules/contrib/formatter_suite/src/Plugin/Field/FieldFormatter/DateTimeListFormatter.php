<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;

/**
 * Formats multiple dates as a list.
 *
 * See the EntityListTrait for a description of list formatting features.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_datetime_list",
 *   label       = @Translation("Formatter Suite - Date & time list"),
 *   weight      = 1001,
 *   field_types = {
 *     "datetime",
 *   }
 * )
 */
class DateTimeListFormatter extends DateTimeDefaultFormatter {
  use EntityListTrait;

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t("Format multi-value date & time fields as a list. Values may be formatted using any of the site's date formats, with an optional time zone.");
  }

}
