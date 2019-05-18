<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampAgoFormatter;

/**
 * Formats multiple timestamps interpreted as time ago as a list.
 *
 * See the EntityListTrait for a description of list formatting features.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_timestamp_time_ago_list",
 *   label       = @Translation("Formatter Suite - Time ago list"),
 *   weight      = 1000,
 *   field_types = {
 *     "timestamp",
 *     "created",
 *     "changed",
 *   }
 * )
 */
class TimestampTimeAgoListFormatter extends TimestampAgoFormatter {
  use EntityListTrait;

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t("Format multi-value timestamp fields as a list. Values are used to calculate a time period between the current date and the field's date. The time period is presented with a selected granularity.");
  }

}
