<?php

namespace Drupal\calendar_systems\CalendarSystems;

abstract class CalendarSystemsPartialImplementation implements CalendarSystemsInterface {

  protected $origin;

  protected $timezone;

  protected $calendar;

  protected $langCode;

  function __construct($tz, $calendar, $lang_code) {
    $this->timezone = $tz;
    $this->origin = new \DateTime('now', $this->timezone);
    $this->calendar = $calendar;
    $this->langCode = $lang_code;
  }

  final function getCalendarName() {
    return $this->calendar;
  }

  function listOptions($name, $required) {
    $none = ['' => ''];
    $year = $this->getBaseYear();
    switch ($name) {
      case 'monthNames':
        $m = [];
        for ($i = 1; $i < 13; $i++) {
          $this->setDateLocale($year, $i, 1);
          $m[$i] = $this->format('F');
        }
        return !$required ? $none + $m : $m;

      case 'monthNamesAbbr':
        $m = [];
        for ($i = 1; $i < 13; $i++) {
          $this->setDateLocale($year, $i, 1);
          $m[$i] = $this->format('M');
        }
        return !$required ? $none + $m : $m;
    }
    return $none;
  }

  abstract function getBaseYear();

  function getLangcode() {
    return $this->langCode;
  }

  protected function getOrigin() {
    return $this->origin;
  }

  // ------------------------------------ FORMAT

  /**
   * Format date time, in gregorian.
   *
   * @param $format
   *
   * @return string
   */
  final function xFormat($format) {
    return $this->origin->format($format);
  }

  /**
   * Put all day and time parts in an array, in gregorian.
   *
   * @return array
   */
  final function xFormatArray() {
    return [
      'year' => intval($this->origin->format('Y')),
      'month' => intval($this->origin->format('n')),
      'day' => intval($this->origin->format('j')),
      'hour' => intval($this->origin->format('G')),
      'minute' => intval($this->origin->format('i')),
      'second' => intval($this->origin->format('s')),
    ];
  }

  final function xSetDate($y, $m, $d) {
    $this->origin->setDate($y, $m, $d);
    return $this;
  }

  final function setTimestamp($timestamp) {
    $this->origin->setTimestamp($timestamp);
    return $this;
  }

  final function getTimestamp() {
    return $this->origin->getTimestamp();
  }

  function validate(array $arr) {
    return NULL;
  }

  final function setTime($hour, $minute, $second) {
    $this->origin->setTime($hour, $minute, $second);
    return $this;
  }

  final function formatArray() {
    return [
      'year' => $this->format('Y'),
      'month' => $this->format('n'),
      'day' => $this->format('j'),
      'hour' => $this->format('G'),
      'minute' => $this->format('i'),
      'second' => $this->format('s'),
    ];
  }

  final protected function tz($tz) {
    $this->origin = new \DateTime('@' . $this->origin->getTimestamp(), $tz);
  }

}
