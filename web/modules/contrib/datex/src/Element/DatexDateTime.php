<?php

namespace Drupal\datex\Element;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Annotation\FormElement;
use Drupal\datex\Datex\DatexDrupalDateTime;

/**
 * @FormElement("datetime")
 */
class DatexDateTime extends Datetime {

  public function getInfo() {
    return ['#date_date_element' => 'text'] + parent::getInfo();
  }

  public static function formatExample($format) {
    return (new DatexDrupalDateTime())->format($format);
  }


  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $e_cal = datex_factory();
    if (!$e_cal) {
      return parent::valueCallback($element, $input, $form_state);
    }

    if ($input !== FALSE) {
      $date_input = $element['#date_date_element'] != 'none' && !empty($input['date']) ? $input['date'] : '';
      $time_input = $element['#date_time_element'] != 'none' && !empty($input['time']) ? $input['time'] : '';
      $date_format = $element['#date_date_element'] != 'none' ? static::getHtml5DateFormat($element) : '';
      $time_format = $element['#date_time_element'] != 'none' ? static::getHtml5TimeFormat($element) : '';
      $timezone = !empty($element['#date_timezone']) ? $element['#date_timezone'] : NULL;
      $e_cal = datex_factory($timezone, 'en');

      // Seconds will be omitted in a post in case there's no entry.
      $date = NULL;
      if (!empty($time_input) && strlen($time_input) == 5) {
        $time_input .= ':00';
      }
      try {
        $date_time_format = trim($date_format . ' ' . $time_format);
        $date_time_input = trim($date_input . ' ' . $time_input);

        if (empty($date_time_input)) {
          // pass
        }
        elseif ($e_cal->parse($date_time_input, $date_time_format)) {
          $date = DrupalDateTime::createFromTimestamp($e_cal->getTimestamp(), $timezone);
        }
        else {
          $form_state->setError($element, t('Date is not valid.'));
        }
      }
      catch (\Exception $e) {
        $date = NULL;
      }
      $input = [
        'date' => $date_input,
        'time' => $time_input,
        'object' => $date,
      ];
    }
    else {
      $date = $element['#default_value'];
      if ($date) {
        $date = DatexDrupalDateTime::convert($date);
      }
      if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
        $input = [
          'date' => $date->format($element['#date_date_format']),
          'time' => $date->format($element['#date_time_format']),
          'object' => $date,
        ];
      }
      else {
        $input = [
          'date' => '',
          'time' => '',
          'object' => NULL,
        ];
      }
    }

    return $input;
  }

  public static function processDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = static::xProcessDatetime($element, $form_state, $complete_form);

    $date = !empty($element['#value']['object']) ? $element['#value']['object'] : NULL;

    if ($element['#date_date_element'] != 'none') {
      $e_cal = datex_factory($element['#date_timezone'], 'en');
      if ($date instanceof DrupalDateTime && !$date->hasErrors()) {

      }
      else {

      }
    }

    if (isset($element['date'])) {
      if ($e_cal) {
        $attr = &$element['date']['#attributes'];
        $date = !empty($date) ? $date : new \DateTime();
        if (isset($element['#date_year_range'])) {
          $range = DatexDateList::datexDatetimeRangeYears($element['#date_year_range'], $date, 'gregorian');
        }
        $e_cal->setDateLocale($range[0], 1, 1);
        $attr['min'] = $e_cal->getTimestamp();
        $e_cal->setDateLocale($range[1], 1, 1);
        $attr['max'] = $e_cal->getTimestamp();

        $attr['data-datex-calendar'] = $e_cal->getCalendarName();
        $attr['autocomplete'] = 'off';
        $attr['data-datex-format'] = $element['#date_date_format'];
      }
    }

    return $element;
  }

  private static function xProcessDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    $format_settings = [];
    // The value callback has populated the #value array.
    $date = !empty($element['#value']['object']) ? $element['#value']['object'] : NULL;

    // Set a fallback timezone.
    if ($date instanceof DrupalDateTime) {
      $element['#date_timezone'] = $date->getTimezone()->getName();
    }
    elseif (empty($element['#timezone'])) {
      $element['#date_timezone'] = drupal_get_user_timezone();
    }

    $element['#tree'] = TRUE;

    if ($element['#date_date_element'] != 'none') {

      $date_format = $element['#date_date_element'] != 'none' ? static::getHtml5DateFormat($element) : '';
      $date_value = !empty($date) ? $date->format($date_format, $format_settings) : $element['#value']['date'];

      // Creating format examples on every individual date item is messy, and
      // placeholders are invalid for HTML5 date and datetime, so an example
      // format is appended to the title to appear in tooltips.
      $extra_attributes = [
        'title' => t('Date (e.g. @format)', ['@format' => static::formatExample($date_format)]),
        'type' => $element['#date_date_element'],
      ];

      // Adds the HTML5 date attributes.
      if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
        $html5_min = clone($date);
        $range = DatexDateList::datexDatetimeRangeYears($element['#date_year_range'], DrupalDateTime::createFromTimestamp($date->getTimestamp()));
        $html5_min->setDate($range[0], 1, 1)->setTime(0, 0, 0);
        $html5_max = clone($date);
        $html5_max->setDate($range[1], 12, 31)->setTime(23, 59, 59);

        $extra_attributes += [
          'min' => $html5_min->format($date_format, $format_settings),
          'max' => $html5_max->format($date_format, $format_settings),
        ];
      }

      $element['date'] = [
        '#type' => 'date',
        '#title' => t('Date'),
        '#title_display' => 'invisible',
        '#value' => $date_value,
        '#attributes' => $element['#attributes'] + $extra_attributes,
        '#required' => $element['#required'],
        '#size' => max(12, strlen($element['#value']['date'])),
        '#error_no_message' => TRUE,
        '#date_date_format' => $element['#date_date_format'],
      ];

      // Allows custom callbacks to alter the element.
      if (!empty($element['#date_date_callbacks'])) {
        foreach ($element['#date_date_callbacks'] as $callback) {
          if (function_exists($callback)) {
            $callback($element, $form_state, $date);
          }
        }
      }
    }

    if ($element['#date_time_element'] != 'none') {

      $time_format = $element['#date_time_element'] != 'none' ? static::getHtml5TimeFormat($element) : '';
      $time_value = !empty($date) ? $date->format($time_format, $format_settings) : $element['#value']['time'];

      // Adds the HTML5 attributes.
      $extra_attributes = [
        'title' => t('Time (e.g. @format)', ['@format' => static::formatExample($time_format)]),
        'type' => $element['#date_time_element'],
        'step' => $element['#date_increment'],
      ];
      $element['time'] = [
        '#type' => 'date',
        '#title' => t('Time'),
        '#title_display' => 'invisible',
        '#value' => $time_value,
        '#attributes' => $element['#attributes'] + $extra_attributes,
        '#required' => $element['#required'],
        '#size' => 12,
        '#error_no_message' => TRUE,
      ];

      // Allows custom callbacks to alter the element.
      if (!empty($element['#date_time_callbacks'])) {
        foreach ($element['#date_time_callbacks'] as $callback) {
          if (function_exists($callback)) {
            $callback($element, $form_state, $date);
          }
        }
      }
    }

    return $element;
  }

}
