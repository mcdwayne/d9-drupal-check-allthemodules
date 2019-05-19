<?php

namespace Drupal\taarikh;

use Drupal\Core\Datetime\DrupalDateTime;
use Hussainweb\DateConverter\Value\DateInterface;

interface TaarikhAlgorithmPluginInterface {

  /**
   * Get the date object from the given julian day.
   *
   * @param int $julian_day
   *   The Julian Day count.
   *
   * @return \Hussainweb\DateConverter\Value\DateInterface
   *   The date object after conversion.
   */
  public function fromJulianDay($julian_day);

  /**
   * Get the julian day from the given date object.
   *
   * @return \Hussainweb\DateConverter\Value\DateInterface
   *   The date object after conversion.
   *
   * @return int
   *   The Julian Day Count for the $date
   */
  public function toJulianDay(DateInterface $date);

  /**
   * Determine if the specified day is valid as per the algorithm.
   *
   * @param int $month_day
   *   The day of the month.
   * @param int $month
   *   The month.
   * @param int $year
   *   The year.
   * @param array $errors
   *   The array to populate with errors and warnings.
   *
   * @return bool
   *   If the date is valid as per the algorithm, true.
   */
  public function isValidDate($month_day, $month, $year, &$errors);

  /**
   * Convert the given date value to a DrupalDateTime.
   *
   * @param \Hussainweb\DateConverter\Value\DateInterface $date
   *   The date value.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The DrupalDateTime object for the given date.
   */
  public function convertToDrupalDateTime(DateInterface $date);

  /**
   * Convert the DrupalDateTime to a date value.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The DrupalDateTime object.
   *
   * @return \Hussainweb\DateConverter\Value\DateInterface
   *   The date value after conversion.
   */
  public function convertFromDrupalDateTime(DrupalDateTime $date);

  /**
   * Convert the specified to a date value.
   *
   * @param string $date
   *   Date string.
   * @param string (optional) $format
   *   The format according to which the given date should be parsed.
   *
   * @return \Hussainweb\DateConverter\Value\DateInterface
   *   The date value after conversion.
   */
  public function convertFromDateFormat($date, $format = NULL);

  /**
   * Construct a date value object suitable for this algorithm.
   *
   * @param int $month_day
   *   The day of the month.
   * @param int $month
   *   The month.
   * @param int $year
   *   The year.
   *
   * @return \Hussainweb\DateConverter\Value\DateInterface
   *   The date value from the date parts specified.
   */
  public function constructDateFromParts($month_day, $month, $year);

  /**
   * Construct a date value object suitable for this algorithm.
   *
   * @param string $date
   *   Date string.
   * @param string (optional) $format
   *   The format according to which the given date should be parsed.
   *
   * @return \Hussainweb\DateConverter\Value\DateInterface
   *   The date value from the date parts specified.
   */
  public function constructDateFromFormat($date, $format = NULL);

}
