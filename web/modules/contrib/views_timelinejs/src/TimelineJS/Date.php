<?php

namespace Drupal\views_timelinejs\TimelineJS;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Converts date strings to TimelineJS3-compatible date arrays.
 */
class Date extends DateTime implements DateInterface {

  /**
   * The original date string that was passed to the constructor.
   *
   * @var string
   */
  protected $dateString;

  /**
   * Constructs a new Date object.
   *
   * @param string $date_string
   *   A string representing a date.
   * @param \DateTimeZone|null $timezone
   *   The date's timezone.
   *
   * @throws \Exception
   *
   * @todo Change the exception to an InvalidArgumentException.
   */
  public function __construct($date_string, DateTimeZone $timezone = NULL) {
    $this->dateString = $date_string;

    // Disallow empty date strings.  They will cause DateTime::__construct() to
    // return a date object with the current time.
    if (empty($date_string)) {
      throw new Exception('Empty date strings are not allowed.');
    }

    // Check for date strings that only include a year value.
    if (is_numeric($date_string)) {
      // Append '-01-01' to year-only values.  By specifying a month and day
      // before the value is parsed, year-only values can be used as input.
      $date_string .= '-01-01';
    }

    parent::__construct($date_string, $timezone);
  }

  /**
   * {@inheritdoc}
   */
  public function buildArray() {
    // The TimelineJS documentation doesn't say anything specific about whether
    // leading zeros should be included in date parts, but the examples do not
    // include them.  Therefore, they are omitted here.
    $exploded_date = explode(',', $this->format('Y,n,j,G,i,s'));

    // Re-key the date array with the property names that TimelineJS expects.
    return [
      'year' => $exploded_date[0],
      'month' => $exploded_date[1],
      'day' => $exploded_date[2],
      'hour' => $exploded_date[3],
      'minute' => $exploded_date[4],
      'second' => $exploded_date[5],
      'display_date' => $this->dateString,
    ];
  }

}
