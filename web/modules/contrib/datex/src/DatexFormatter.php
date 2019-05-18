<?php

namespace Drupal\datex;

use Drupal\Core\Datetime\DateFormatter;

/**
 * Extends core's formatter with a locale and translation supporting one.
 */
class DatexFormatter extends DateFormatter {

  /**
   * {@inheritdoc}
   */
  public function format($timestamp, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL) {
    if ($type === 'custom' && $format === 'c' ||
      $format === \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT ||
      $format === \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATE_STORAGE_FORMAT) {
      return parent::format($timestamp, $type, $format, $timezone, $langcode);
    }

    if (!isset($timezone)) {
      $timezone = date_default_timezone_get();
    }
    // Store DateTimeZone objects in an array rather than repeatedly
    // constructing identical objects over the life of a request.
    if (!isset($this->timezones[$timezone])) {
      $this->timezones[$timezone] = timezone_open($timezone);
    }

    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
    }

    $cal = datex_factory($this->timezones[$timezone]);
    if (!$cal) {
      return parent::format($timestamp, $type, $format, $timezone, $langcode);
    }
    $cal->setTimestamp($timestamp);

    // If we have a non-custom date format use the provided date format pattern.
    if ($type !== 'custom') {
      if ($date_format = $this->dateFormat($type, $langcode)) {
        $format = $date_format->getPattern();
      }
    }

    // Fall back to the 'medium' date format type if the format string is
    // empty, either from not finding a requested date format or being given an
    // empty custom format string.
    if (empty($format)) {
      $format = $this->dateFormat('fallback', $langcode)->getPattern();
    }
    return $cal->format($format);
  }

}
