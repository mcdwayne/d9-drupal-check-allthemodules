<?php

namespace Drupal\persian_date\Service\Formatter;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\persian_date\Converter\PersianDateConverter;
use Drupal\persian_date\Converter\PersianDateFactory;
use Drupal\persian_date\PersianLanguageDiscovery;

class PersianDateFormatter extends DateFormatter
{
    public function format($timestamp, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL)
    {
        // return default formatter if website is not persian
        // or caller needs machine-readable data
        if (!PersianLanguageDiscovery::isPersian() || $this->isForMetaTag()) {
            return parent::format($timestamp, $type, $format, $timezone, $langcode);
        }

        if (!isset($timezone)) {
            $timezone = drupal_get_user_timezone();
        }

        // Store DateTimeZone objects in an array rather than repeatedly
        // constructing identical objects over the life of a request.
        if (!isset($this->timezones[$timezone])) {
            $this->timezones[$timezone] = timezone_open($timezone);
        }

        if (empty($langcode)) {
            $langcode = $this->languageManager->getCurrentLanguage()->getId();
        }

        $date = PersianDateFactory::buildFromTimestamp($timestamp, $this->timezones[$timezone]);

        // If we have a non-custom date format use the provided date format pattern.
        if ($type !== 'custom') {
            if ($date_format = $this->dateFormat($type, $langcode)) {
                $format = $date_format->getPattern();
            }
        }

        // called by query builder
        if ($type === 'custom' & $format === DATETIME_DATETIME_STORAGE_FORMAT) {
            // convert shamsi to georgian
            $date = parent::format($timestamp, $type, $format, $timezone, $langcode);
            $year = explode('-', $date)[0];
            // if date is shamsi
            if (!is_georgian_year($year)) {
                $other = PersianDateConverter::normalizeDate(new \DateTime($date));
                $date = parent::format($other->getTimestamp(), $type, $format, $timezone, $langcode);
            }
            return $date;
        }

        // Fall back to the 'medium' date format type if the format string is
        // empty, either from not finding a requested date format or being given an
        // empty custom format string.
        if (empty($format)) {
            $format = $this->dateFormat('fallback', $langcode)->getPattern();
        }
        return $date->format($format);
    }

  private function isForMetaTag() {
    foreach (debug_backtrace() as $trace) {
      if (!isset($trace['file'])) {
        continue;
      }
      $isMetatagModuleCalling = strpos($trace['file'], '/metatag/') !== FALSE;
      $isRdfModuleCalling = strpos($trace['file'], '/rdf/') !== FALSE;

      if ($isMetatagModuleCalling || $isRdfModuleCalling) {
        return TRUE;
      }
    }
    return FALSE;
  }
}
