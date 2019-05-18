<?php

namespace Drupal\hms_field;

/**
 * Provides a service to handle various hms related functionality.
 *
 * @package Drupal\hms_field
 */
class HMSService implements HMSServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function add_multi_search_tokens($item) {
    return '/' . $item . '+/';
  }

  /**
   * {@inheritdoc}
   */
  public function array_get_nested_value(array &$array, array $parents, &$key_exists = NULL) {
    $ref = &$array;
    foreach ($parents as $parent) {
      if (is_array($ref) && array_key_exists($parent, $ref)) {
        $ref = &$ref[$parent];
      }
      else {
        $key_exists = FALSE;
        $null = NULL;
        return $null;
      }
    }
    $key_exists = TRUE;
    return $ref;
  }

  /**
   * {@inheritdoc}
   */
  public function factor_map($return_full = FALSE) {
    $factor = drupal_static(__FUNCTION__);
    if (empty($factor)) {
      $factor = [
        'w' => [
          'factor value' => 604800,
          'label single' => 'week',
          'label multiple' => 'weeks'
        ],
        'd' => [
          'factor value' => 86400,
          'label single' => 'day',
          'label multiple' => 'days'
        ],
        'h' => [
          'factor value' => 3600,
          'label single' => 'hour',
          'label multiple' => 'hours'
        ],
        'm' => [
          'factor value' => 60,
          'label single' => 'minute',
          'label multiple' => 'minutes'
        ],
        's' => [
          'factor value' => 1,
          'label single' => 'second',
          'label multiple' => 'seconds'
        ],
      ];
      \Drupal::moduleHandler()->alter('hms_factor', $factor);
    }

    if ($return_full) {
      return $factor;
    }

    // We only return the factor value here.
    // for historical reasons we also check if value is an array.
    $return = [];
    foreach ($factor as $key => $val) {
      $value = (is_array($val) ? $val['factor value'] : $val);
      $return[$key] = $value;
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function format_options() {
    $format = drupal_static(__FUNCTION__);
    if (empty($format)) {
      $format = [
        'ISO 8601 based' => [
          'h:mm' => 'h:mm',
          'hh:mm:ss' => 'hh:mm:ss',
          'h:mm:ss' => 'h:mm:ss',
          'm:ss' => 'm:ss',
          'h' => 'h',
          'm' => 'm',
          's' => 's'
        ],
        'Space separated' => [
          'hms' => 'e.q. 3h 15m 30s'
        ]
      ];
      \Drupal::moduleHandler()->alter('hms_format', $format);
    }
    return $format;
  }

  /**
   * {@inheritdoc}
   */
  public function formatted_to_seconds($str, $format = 'h:m:s', $element = [], $form_state = []) {
    if (!strlen($str)) {
      return NULL;
    }
    elseif ($str == '0') {
      return 0;
    }
    $value = 0;
    $error = FALSE;

    // Input validation for space separated format.
    if($format == 'hms') {
      $preg = [];
      if ((is_numeric($str) || preg_match('/^(?P<H>[-]{0,1}[0-9]{1,5}(\.[0-9]{1,3})?)$|^(?P<negative>[-]{0,1})(((?P<w>[0-9.]{1,5})w)?((?P<d>[0-9.]{1,5})d)?((?P<h>[0-9.]{1,5})h)?([ ]{0,1})((?P<m>[0-9.]{1,05})m)?([ ]{0,1})((?P<s>[0-9.]{1,5})s)?)/', $str, $preg))) {
        $error = TRUE;
        foreach($preg as $code => $val) {
          if (!is_numeric($val)) {
            continue;
          }
          switch ($code) {
            case 'w':
              $error = FALSE;
              $value += $val * 604800;
              break;
            case 'd':
              $error = FALSE;
              $value += $val * 86400;
              break;
            case 'h':
            case 'H':
              $error = FALSE;
              $value += $val * 3600;
              break;
              case 'm':
                $error = FALSE;
                $value += $val * 60;
                break;
            case 's':
              $error = FALSE;
              $value += $val;
              break;
            default:
            break;
          }
        }
        if (!empty($preg['negative'])) {
          $value = $value * -1;
        }
        if ($error == 0) {
            return $value;
        }
      }
      else {
        $error = TRUE;
      }
    }

    // Input validation ISO 8601 based.
    $preg_string = preg_replace(['/[h]{1,6}/', '/[m]{1,2}|[s]{1,2}/'], ['([0-9]{1,6})', '([0-9]{1,2})'], $format);
    if (!preg_match("@^".$preg_string."$@", $str) && !preg_match('/^[0-9]{1,6}([,.][0-9]{1,6})?$/', $str)) {
      $error = TRUE;
    }

    // Does not follow space separated format.
    if ($error) {
      if(!empty($form_state)) {
        $form_state->setErrorByName('field_name', t('The %name value is in wrong format, check in field settings.', ['%name' => t($element['#title'])]));
      }
      return FALSE;
    }

    // is the value negative?
    $negative = FALSE;
    if (substr($str, 0, 1) == '-') {
      $negative = TRUE;
      $str = substr($str, 1);
    }

    $factor_map = $this->factor_map();
    $search = $this->normalize_format($format);

    for ($i = 0; $i < strlen($search); $i++) {
      // Is this char in the factor map?
      if (isset($factor_map[$search[$i]])) {
        $factor = $factor_map[$search[$i]];
        // What is the next seperator to search for?
        $bumper = '$';
        if (isset($search[$i + 1])) {
          $bumper = '(' . preg_quote($search[$i + 1], '/') . '|$)';
        }
        if (preg_match_all('/^(.*)' . $bumper . '/U', $str, $matches)) {
          // Replace , with .
          $num = str_replace(',', '.', $matches[1][0]);
          // Return error when found string is not numeric
          if (!is_numeric($num)) {
            return FALSE;
          }
          // Shorten $str
          $str = substr($str, strlen($matches[1][0]));
          // Calculate value
          $value += ($num * $factor);
        }

      }
      elseif (substr($str, 0, 1) == $search[$i]) {
        // Expected this value, cut off and go ahead.
        $str = substr($str, 1);
      }
      else {
        // Does not follow format.
        return FALSE;
      }
      if (!strlen($str)) {
        // No more $str to investigate.
        break;
      }
    }

    if ($negative) {
      $value = 0 - $value;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize_format($format) {
    $keys = array_keys($this->factor_map());
    $search_keys = array_map([$this, 'add_multi_search_tokens'], $keys);
    return preg_replace($search_keys, $keys, $format);
  }

  /**
   * {@inheritdoc}
   */
  public function seconds_to_formatted($seconds, $format = 'h:mm', $leading_zero = TRUE) {

    // Return NULL on empty string.
    if ($seconds === '' || is_null($seconds)) {
      return NULL;
    }

    $factor = $this->factor_map();
    // We need factors, biggest first.
    arsort($factor, SORT_NUMERIC);
    $values = [];
    $left_over = $seconds;
    $str = '';

    if ($seconds < 0) {
      $str .= '-';
      $left_over = abs($left_over);
    }

    // Space separated format
    if ($format == 'hms') {
      foreach ($factor as $key => $val) {
        if ($left_over == 0) {
          break;
        }
        $values[$key] = floor($left_over / $factor[$key]);
        if($values[$key]) {
          $left_over -= ($values[$key] * $factor[$key]);
          $str .= $values[$key] . $key . ' ';
        }
      }
    }

    // ISO based formats
    else {
      foreach ($factor as $key => $val) {
        if (strpos($format, $key) === FALSE) {
          continue; // Not in our format, please go on, so we can plus this on a value in our format.
        }
        if ($left_over == 0) {
          $values[$key] = 0;
          continue;
        }
        $values[$key] = floor($left_over/$factor[$key]);
        $left_over -= ($values[$key] * $factor[$key]);
      }
      $format = explode(':', $format);
      foreach($format as $key) {
        if (!$leading_zero && (empty($values[substr($key, 0, 1)]) || !$values[substr($key, 0, 1)])) {
          continue;
        }
        $leading_zero = TRUE;
        $str .= sprintf('%0'.strlen($key).'d', $values[substr($key, 0, 1)]) . ':';
      }
      if (!strlen($str)) {
        $key = array_pop($format);
        $str = sprintf('%0'.strlen($key).'d', 0) . ':';
      }
    }

    return substr($str, 0, -1);
  }

  /**
   * {@inheritdoc}
   */
  public function isValid($input, $format, $element = [], $format_state = []) {
    if ($this->formatted_to_seconds($input, $format, $element, $format_state) !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }
}
