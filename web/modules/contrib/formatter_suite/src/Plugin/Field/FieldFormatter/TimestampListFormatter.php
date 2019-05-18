<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;

/**
 * Formats multiple timestamps as a list.
 *
 * See the EntityListTrait for a description of list formatting features.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_timestamp_list",
 *   label       = @Translation("Formatter Suite - Timestamp list"),
 *   weight      = 1001,
 *   field_types = {
 *     "timestamp",
 *     "created",
 *     "changed",
 *   }
 * )
 */
class TimestampListFormatter extends TimestampFormatter {
  use EntityListTrait;

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t("Format multi-value timestamp fields as a list. Values may be formatted using any of the site's date formats, with an optional time zone.");
  }

}
