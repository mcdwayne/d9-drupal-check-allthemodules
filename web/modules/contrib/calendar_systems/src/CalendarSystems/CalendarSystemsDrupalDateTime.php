<?php

namespace Drupal\calendar_systems\CalendarSystems;

use Drupal\Core\Datetime\DrupalDateTime;

class CalendarSystemsDrupalDateTime extends DrupalDateTime {

  function format($format, $settings = []) {
    //    $langcode = !empty($settings['langcode']) ? $settings['langcode'] : $this->langcode;
    $value = '';
    try {
      if (!$this->hasErrors()) {
        if (isset($settings['timezone'])) {
          $tz = new \DateTimeZone($settings['timezone']);
        }
        else {
          $tz = $this->getTimezone();
        }
        $cal = calendar_systems_factory($tz, 'en');
        if (!$cal) {
          return parent::format($format, $settings);
        }
        return $cal->setTimestamp($this->getTimestamp())->format($format);
      }
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }
    return $value;
  }

  function origin(): DrupalDateTime {
    $me = new DrupalDateTime(
      $this->getTimestamp(),
      $this->getTimezone(),
      ['langcode' => $this->langcode]
    );

    $me->formatTranslationCache = $this->formatTranslationCache;
    $me->stringTranslation = $this->stringTranslation;

    $me->inputTimeRaw = $this->inputTimeRaw;
    $me->inputTimeAdjusted = $this->inputTimeAdjusted;
    $me->inputTimeZoneRaw = $this->inputTimeZoneRaw;
    $me->inputTimeZoneAdjusted = $this->inputTimeZoneAdjusted;
    $me->inputFormatRaw = $this->inputFormatRaw;
    $me->inputFormatAdjusted = $this->inputFormatAdjusted;
    $me->langcode = $this->langcode;
    $me->errors = $this->errors;
    $me->dateTimeObject = $this->dateTimeObject;

    $me->setTimestamp($this->getTimestamp());

    return $me;
  }

  static function convert(DrupalDateTime $dateTime): CalendarSystemsDrupalDateTime {
    $me = new CalendarSystemsDrupalDateTime(
      $dateTime->getTimestamp(),
      $dateTime->getTimezone(),
      ['langcode' => $dateTime->langcode]
    );

    $me->formatTranslationCache = $dateTime->formatTranslationCache;
    $me->stringTranslation = $dateTime->stringTranslation;

    $me->inputTimeRaw = $dateTime->inputTimeRaw;
    $me->inputTimeAdjusted = $dateTime->inputTimeAdjusted;
    $me->inputTimeZoneRaw = $dateTime->inputTimeZoneRaw;
    $me->inputTimeZoneAdjusted = $dateTime->inputTimeZoneAdjusted;
    $me->inputFormatRaw = $dateTime->inputFormatRaw;
    $me->inputFormatAdjusted = $dateTime->inputFormatAdjusted;
    $me->langcode = $dateTime->langcode;
    $me->errors = $dateTime->errors;
    $me->dateTimeObject = $dateTime->dateTimeObject;

    $me->setTimestamp($dateTime->getTimestamp());

    return $me;
  }

}
