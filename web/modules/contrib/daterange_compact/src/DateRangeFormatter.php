<?php

namespace Drupal\daterange_compact;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a service to handle formatting of date/time ranges.
 */
class DateRangeFormatter implements DateRangeFormatterInterface {

  /**
   * The date range format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateRangeFormatStorage;

  /**
   * The core date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs the date range formatter service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The core date formatter.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter) {
    $this->dateRangeFormatStorage = $entity_type_manager->getStorage('date_range_format');
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function formatDateRange($start_timestamp, $end_timestamp, $type = 'medium', $timezone = NULL, $langcode = NULL) {
    $start_date_time = DrupalDateTime::createFromTimestamp($start_timestamp, $timezone);
    $end_date_time = DrupalDateTime::createFromTimestamp($end_timestamp, $timezone);

    /** @var \Drupal\daterange_compact\Entity\DateRangeFormatInterface $entity */
    $entity = $this->dateRangeFormatStorage->load($type);
    $date_settings = $entity->getDateSettings();
    $default_pattern = $date_settings['default_pattern'];
    $separator = $date_settings['separator'] ?: '';

    // Strings containing the ISO-8601 representations of the start and end
    // date can be used to determine if the day, month or year is the same.
    $start_iso_8601 = $start_date_time->format('Y-m-d');
    $end_iso_8601 = $end_date_time->format('Y-m-d');

    if ($start_iso_8601 === $end_iso_8601) {
      // The range is a single day.
      return $this->dateFormatter->format($start_timestamp, 'custom',
        $default_pattern, $timezone, $langcode);
    }
    elseif (substr($start_iso_8601, 0, 7) === substr($end_iso_8601, 0, 7)) {
      // The range spans several days within the same month.
      $start_pattern = isset($date_settings['same_month_start_pattern']) ? $date_settings['same_month_start_pattern'] : '';
      $end_pattern = isset($date_settings['same_month_end_pattern']) ? $date_settings['same_month_end_pattern'] : '';
      if ($start_pattern && $end_pattern) {
        $start_text = $this->dateFormatter->format($start_timestamp, 'custom', $start_pattern, $timezone, $langcode);
        $end_text = $this->dateFormatter->format($end_timestamp, 'custom', $end_pattern, $timezone, $langcode);
        return $start_text . $separator . $end_text;
      }
    }
    elseif (substr($start_iso_8601, 0, 4) === substr($end_iso_8601, 0, 4)) {
      // The range spans several months within the same year.
      $start_pattern = isset($date_settings['same_year_start_pattern']) ? $date_settings['same_year_start_pattern'] : '';
      $end_pattern = isset($date_settings['same_year_end_pattern']) ? $date_settings['same_year_end_pattern'] : '';
      if ($start_pattern && $end_pattern) {
        $start_text = $this->dateFormatter->format($start_timestamp, 'custom', $start_pattern, $timezone, $langcode);
        $end_text = $this->dateFormatter->format($end_timestamp, 'custom', $end_pattern, $timezone, $langcode);
        return $start_text . $separator . $end_text;
      }
    }

    // Fallback: show the start and end dates in full using the default
    // pattern. This is the case if the range spans different years,
    // or if the other patterns are not specified.
    $start_text = $this->dateFormatter->format($start_timestamp, 'custom', $default_pattern, $timezone, $langcode);
    $end_text = $this->dateFormatter->format($end_timestamp, 'custom', $default_pattern, $timezone, $langcode);
    return $start_text . $separator . $end_text;
  }

  /**
   * {@inheritdoc}
   */
  public function formatDateTimeRange($start_timestamp, $end_timestamp, $type = 'medium', $timezone = NULL, $langcode = NULL) {
    $start_date_time = DrupalDateTime::createFromTimestamp($start_timestamp, $timezone);
    $end_date_time = DrupalDateTime::createFromTimestamp($end_timestamp, $timezone);

    /** @var \Drupal\daterange_compact\Entity\DateRangeFormatInterface $entity */
    $entity = $this->dateRangeFormatStorage->load($type);
    $datetime_settings = $entity->getDateTimeSettings();
    $default_pattern = $datetime_settings['default_pattern'];
    $separator = $datetime_settings['separator'] ?: '';

    // Strings containing the ISO-8601 representations of the start and end
    // datetime can be used to determine if the date and/or time are the same.
    $start_iso_8601 = $start_date_time->format('Y-m-d\TH:i:s');
    $end_iso_8601 = $end_date_time->format('Y-m-d\TH:i:s');

    if ($start_iso_8601 === $end_iso_8601) {
      // The range is a single date and time.
      return $this->dateFormatter->format($start_timestamp, 'custom',
          $default_pattern, $timezone, $langcode);
    }
    elseif (substr($start_iso_8601, 0, 10) == substr($end_iso_8601, 0, 10)) {
      // The range is contained within a single day.
      $start_pattern = isset($datetime_settings['same_day_start_pattern']) ? $datetime_settings['same_day_start_pattern'] : '';
      $end_pattern = isset($datetime_settings['same_day_end_pattern']) ? $datetime_settings['same_day_end_pattern'] : '';
      if ($start_pattern && $end_pattern) {
        $start_text = $this->dateFormatter->format($start_timestamp, 'custom', $start_pattern, $timezone, $langcode);
        $end_text = $this->dateFormatter->format($end_timestamp, 'custom', $end_pattern, $timezone, $langcode);
        return $start_text . $separator . $end_text;
      }
    }

    // Fallback: show the start and end datetimes in full using the default
    // pattern. This is the case if the range spans different days,
    // or if the other patterns are not specified.
    $start_text = $this->dateFormatter->format($start_timestamp, 'custom', $default_pattern, $timezone, $langcode);
    $end_text = $this->dateFormatter->format($end_timestamp, 'custom', $default_pattern, $timezone, $langcode);
    return $start_text . $separator . $end_text;
  }

}
