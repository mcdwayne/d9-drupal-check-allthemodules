<?php

namespace Drupal\config_templates;

use Drupal\config_update\ConfigDiffer;

/**
 * Provides methods related to config differences.
 */
class ConfigTemplatesDiffer extends ConfigDiffer {

  /**
   * Various string representations of different NULL/Empty data.
   */
  const NULL_VALUE = '(NULL)';
  const NULL_STRING = '';
  const NULL_ARRAY = '(NULL_ARRAY)';

  /**
   * {@inheritdoc}
   *
   * Same as parent but prevent empty arrays from being removed.
   */
  protected function normalize($config) {
    // Remove "ignore" elements.
    foreach ($this->ignore as $element) {
      unset($config[$element]);
    }

    // Recursively normalize remaining elements, if they are arrays.
    foreach ($config as $key => $value) {
      if (is_array($value)) {
        $new = $this->normalize($value);
        if (count($new)) {
          $config[$key] = $new;
        }
        else {
          // Do not remove empty arrays.
          // This is the line that was changed from the parent function.
          // unset($config[$key]);
          $config[$key] = [];
        }
      }
    }

    // Sort and return.
    ksort($config);
    return $config;
  }

  /**
   * {@inheritdoc}
   *
   * Same as parent except handling of null data values.
   */
  protected function format($config, $prefix = '') {
    $lines = [];

    foreach ($config as $key => $value) {
      $section_prefix = ($prefix) ? $prefix . $this->hierarchyPrefix . $key : $key;

      if (empty($value)) {
        // Handle Null/Empty data here.
        $lines[] = $section_prefix . $this->valuePrefix . self::encodeNullValue($value);
      }
      elseif (is_array($value)) {
        $lines[] = $section_prefix;
        $newlines = $this->format($value, $section_prefix);
        foreach ($newlines as $line) {
          $lines[] = $line;
        }
      }
      else {
        $lines[] = $section_prefix . $this->valuePrefix . $value;
      }
    }

    return $lines;
  }

  /**
   * Convert the null string data into the proper result.
   *
   * @param string $value
   * @return array|null|string
   */
  public function decodeNullValue($value) {
    switch ($value) {
      case self::NULL_VALUE:
        return NULL;
      case self::NULL_STRING:
        return '';
      case self::NULL_ARRAY:
        return [];
    }
    return $value;
  }

  /**
   * Convert the data into a null string value.
   *
   * @param mixed $value
   * @return string
   */
  public function encodeNullValue($value) {
    if (empty($value)) {
      if (is_array($value)) {
        return self::NULL_ARRAY;
      }
      else if (is_string($value)) {
        return self::NULL_STRING;
      }
      else if (is_null($value)) {
        return self::NULL_VALUE;
      }
    }
    return $value;
  }

}
