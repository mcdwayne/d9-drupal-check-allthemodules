<?php

namespace Drupal\date_ap_style;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ApStyleDateFormatter.
 */
class ApStyleDateFormatter {

  use StringTranslationTrait;

  /**
   * Language manager for retrieving default langcode when none is specified.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * Return month format code based on AP Style rules.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   Drupal date object.
   *
   * @return string
   *   Date format string.
   */
  private function formatMonth(DrupalDateTime $date) {
    switch ($date->format('m')) {
      case '03':
      case '04':
      case '05':
      case '06':
      case '07':
        // Short months get the full print out of their name.
        $month_format = 'F';
        break;

      case '09':
        // September is abbreviated to 'Sep' by PHP but we want 'Sept'.
        $month_format = 'M\t.';
        break;

      default:
        // Other months get an abbreviated print out followed by a period.
        $month_format = 'M.';
        break;
    }
    return $month_format;
  }

  /**
   * Format a timestamp to an AP style date format.
   *
   * @param int $timestamp
   *   The timestamp to convert.
   * @param array $options
   *   An array of options that affect how the date string is formatted.
   * @param mixed $timezone
   *   \DateTimeZone object, time zone string or NULL. NULL uses the
   *   default system time zone. Defaults to NULL.
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   The formatted date string.
   */
  public function formatTimestamp($timestamp, array $options = [], $timezone = NULL, $langcode = NULL) {
    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
    }

    // If no timezone is specified, use the user's if available, or the site
    // or system default.
    if (empty($timezone)) {
      $timezone = drupal_get_user_timezone();
    }

    // Create a DrupalDateTime object from the timestamp and timezone.
    $datetime_settings = [
      'langcode' => $langcode,
    ];

    $date_string = '';
    $format_date = '';

    // Create a DrupalDateTime object from the timestamp and timezone.
    $date = DrupalDateTime::createFromTimestamp($timestamp, $timezone, $datetime_settings);
    $now = new DrupalDateTime('now', $timezone, $datetime_settings);

    if (isset($options['use_today']) && $options['use_today'] && $date->format('Y-m-d') == $now->format('Y-m-d')) {
      $date_string = $this->t('today');
      if (isset($options['cap_today']) && $options['cap_today']) {
        $date_string = ucfirst($date_string);
      }
    }
    // Determine if the date is within the current week and set final output.
    elseif (isset($options['display_day']) && $options['display_day'] && $date->format('W o') == $now->format('W o')) {
      $format_date .= 'l';
    }
    else {
      $format_date .= $this->formatMonth($date) . ' j';
      if ((isset($options['always_display_year']) && $options['always_display_year']) || $date->format('Y') != $now->format('Y')) {
        $format_date .= ', Y';
      }
    }

    $date_string .= $date->format($format_date);

    if (isset($options['display_time']) && $options['display_time']) {
      $capital = isset($options['capitalize_noon_and_midnight']) && $options['capitalize_noon_and_midnight'];

      switch ($date->format('H:i')) {
        case '00:00':
          if (isset($options['use_all_day']) && $options['use_all_day']) {
            $ap_time_string = $this->t('All Day');
          }
          else {
            $ap_time_string = $this->t('midnight');
            if ($capital) {
              $ap_time_string = ucfirst($ap_time_string);
            }
          }
          break;

        case '12:00':
          $ap_time_string = $this->t('noon');
          if ($capital) {
            $ap_time_string = ucfirst($ap_time_string);
          }
          break;

        default:
          if ($date->format('i')) {
            // Don't display the minutes if it's the top of the hour.
            $ap_time_string = $date->format('g a');
          }
          else {
            $ap_time_string = $date->format('g:i a');
          }
          break;
      }

      // Format the meridian if it's there.
      $ap_time_string = str_replace(['am', 'pm'], ['a.m.', 'p.m.'], $ap_time_string);

      if (isset($options['time_before_date']) && $options['time_before_date']) {
        $output = $ap_time_string . ', ' . $date_string;
      }
      else {
        $output = $date_string . ', ' . $ap_time_string;
      }
    }
    else {
      $output = $date_string;
    }

    return $output;
  }

  /**
   * Format a timestamp to an AP style date format.
   *
   * @param array $timestamps
   *   The start and end timestamps to convert.
   * @param array $options
   *   An array of options that affect how the date string is formatted.
   * @param mixed $timezone
   *   \DateTimeZone object, time zone string or NULL. NULL uses the
   *   default system time zone. Defaults to NULL.
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   The formatted date string.
   */
  public function formatRange(array $timestamps, array $options = [], $timezone = NULL, $langcode = NULL) {
    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
    }

    // If no timezone is specified, use the user's if available, or the site
    // or system default.
    if (empty($timezone)) {
      $timezone = drupal_get_user_timezone();
    }

    // Create a DrupalDateTime object from the timestamp and timezone.
    $datetime_settings = [
      'langcode' => $langcode,
    ];

    $date_output = '';
    $time_output = '';

    if (!empty($timestamps)) {
      // Create a DrupalDateTime object from the timestamp and timezone.
      $start_stamp = DrupalDateTime::createFromTimestamp($timestamps['start'], $timezone, $datetime_settings);
      $now = new DrupalDateTime('now', $timezone, $datetime_settings);
      if (!empty($timestamps['end']) or $timestamps['end'] != 0) {
        $end_stamp = DrupalDateTime::createFromTimestamp($timestamps['end'], $timezone, $datetime_settings);
      }
      else {
        $end_stamp = $start_stamp;
      }
      $format_start_date = '';
      $format_end_date = '';
      $time_start = '';
      $time_end = '';
      $time_start_string = '';
      $time_end_string = '';

      if ($start_stamp->format('Y-m-d') == $end_stamp->format('Y-m-d')) {
        // The Y-M-D is identical.
        $format_start_date = $this->formatMonth($start_stamp) . ' j';
        // Display Y if not equal to current year or option set to always show
        // year.
        if ((isset($options['always_display_year']) && $options['always_display_year']) || $start_stamp->format('Y') != $now->format('Y')) {
          $format_start_date .= ', Y';
        }
      }
      elseif ($start_stamp->format('Y-m') == $end_stamp->format('Y-m')) {
        // The Y-M is identical, but different D.
        $format_start_date = $this->formatMonth($start_stamp) . ' j';
        $format_end_date = 'j';
        // Display Y if end_time year not equal to current year.
        if ((isset($options['always_display_year']) && $options['always_display_year']) || $end_stamp->format('Y') != $now->format('Y')) {
          $format_end_date .= ', Y';
        }
      }
      elseif ($start_stamp->format('Y') == $end_stamp->format('Y')) {
        // The Y is identical, but different M-D.
        $format_start_date = $this->formatMonth($start_stamp) . ' j';
        $format_end_date = $this->formatMonth($end_stamp) . ' j';
        // Display Y if end_time year not equal to current year.
        if ((isset($options['always_display_year']) && $options['always_display_year']) || $end_stamp->format('Y') != $now->format('Y')) {
          $format_end_date .= ', Y';
        }
      }
      elseif ($start_stamp->format('m-d') == $end_stamp->format('m-d')) {
        // The M-D is identical, but different Y.
        $format_start_date = $this->formatMonth($start_stamp) . ' j, Y';
        $format_end_date = 'Y';
      }
      elseif ($start_stamp->format('d') == $end_stamp->format('d')) {
        $format_start_date = $this->formatMonth($start_stamp) . ' j';
        $format_end_date = $this->formatMonth($end_stamp) . ' j';
      }
      else {
        // All three are different.
        $format_start_date = $this->formatMonth($start_stamp) . ' j, Y';
        $format_end_date = $this->formatMonth($end_stamp) . ' j, Y';
      }

      if (isset($options['display_time']) && $options['display_time']) {
        if (isset($options['use_all_day']) && $options['use_all_day'] && ($start_stamp->format('H:i') == '00:00' || $start_stamp->format('gia') == $end_stamp->format('gia'))) {
          $time_output = $this->t('All Day');
        }
        else {
          $capital = (isset($options['capitalize_noon_and_midnight']) && $options['capitalize_noon_and_midnight']);
          // Don't display the minutes if it's the top of the hour.
          $time_start = $start_stamp->format('i') == '00' ? 'g' : 'g:i';
          // If same start/end meridians and different start/end time,
          // don't include meridian in start.
          $time_start .= ($start_stamp->format('a') == $end_stamp->format('a') && $start_stamp->format('gia') != $end_stamp->format('gia') ? '' : ' a');

          // Set preformatted start and end times based on.
          // Replace 12:00 am with Midnight & 12:00 pm with Noon.
          switch ($start_stamp->format('H:i')) {
            case '00:00':
              $time_start_string = $this->t('midnight');
              if ($capital) {
                $time_start_string = ucfirst($time_start_string);
              }
              break;

            case '12:00':
              $time_start_string = $this->t('noon');
              if ($capital) {
                $time_start_string = ucfirst($time_start_string);
              }
              break;
          }
          if ($start_stamp->format('Hi') != $end_stamp->format('Hi')) {
            $time_end = $end_stamp->format('i') == '00' ? 'g a' : 'g:i a';
            switch ($end_stamp->format('H:i')) {
              case '00:00':
                $time_end_string = $this->t('midnight');
                if ($capital) {
                  $time_end_string = ucfirst($time_end_string);
                }
                break;

              case '12:00':
                $time_end_string = $this->t('noon');
                if ($capital) {
                  $time_end_string = ucfirst($time_end_string);
                }
                break;
            }
          }

          if (!empty($time_start)) {
            $time_output .= $time_start_string ?: $start_stamp->format($time_start);
          }
          if (!empty($time_end)) {
            $time_output .= (isset($options['separator']) && $options['separator'] == 'endash' ? ' &ndash; ' : ' to ');
            $time_output .= $time_end_string ?: $end_stamp->format($time_end);
          }

          $time_output = str_replace(['am', 'pm'], ['a.m.', 'p.m.'], $time_output);
        }
      }

      $date_output = $start_stamp->format($format_start_date);
      if (!empty($format_end_date)) {
        $date_output .= (isset($options['separator']) && $options['separator'] == 'endash' ? ' &ndash; ' : ' to ') . $end_stamp->format($format_end_date);
      }

      if (!empty($time_output) && isset($options['time_before_date']) && $options['time_before_date']) {
        $output = $time_output . ', ' . $date_output;
      }
      elseif (!empty($time_output)) {
        $output = $date_output . ', ' . $time_output;
      }
      else {
        $output = $date_output;
      }
      return $output;
    }

    return '';
  }

}
