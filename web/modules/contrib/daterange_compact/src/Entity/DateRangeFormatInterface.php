<?php

namespace Drupal\daterange_compact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining a date range format.
 */
interface DateRangeFormatInterface extends ConfigEntityInterface {

  /**
   * The settings for use when displaying date only ranges.
   *
   * @return array
   *   An array with the following keys:
   *     - "default_pattern" - the default format pattern, used when the
   *          start and end values are the same, or the range spans multiple
   *          years
   *     - "separator" - the separator string to place in between the
   *          start and end values
   *     - "same_day_start_pattern" - the format pattern to use for the
   *          start date, when the range is contained within a single day
   *     - "same_day_end_pattern" - the format pattern to use for the
   *          end date, when the range is contained within a single day
   */
  public function getDateSettings();

  /**
   * The settings for use when displaying date & time ranges.
   *
   * @return array
   *   An array with the following keys:
   *     - "default_pattern" - the default format pattern, used when the
   *          start and end values are the same, or the range spans
   *          multiple days
   *     - "separator" - the separator string to place in between the
   *          start and end values
   *     - "same_day_start_pattern" - the format pattern to use for the
   *          start date & time, when the range is contained within a single day
   *     - "same_day_end_pattern" - the format pattern to use for the
   *          end date & time, when the range is contained within a single day
   */
  public function getDateTimeSettings();

}
