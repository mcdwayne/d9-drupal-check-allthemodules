<?php

namespace Drupal\color_widget\Services;

/**
 * Class ColorHelper.
 *
 * @package Drupal\color_widget\Services
 */
class ColorHelper {

  /**
   * Convert each line of the value from a textarea to an array.
   *
   * Use the pipeline "|" to be able to set the keys of the array.
   *
   * @param string $value
   *   The value of the field.
   *
   * @return array
   *   The return array.
   */
  public function convertTextareaToArray($value) {
    // Remove extra characters.
    $toRemove = [
      // Carriage return.
      "\r",
      // Tab.
      "\t",
      // NUL-byte.
      "\0",
      // Vertical tab.
      "\x0B",
    ];
    $cleaned = trim(str_replace($toRemove, '', $value));
    // Convert the lines to array values.
    $convertedToArray = explode("\n", $cleaned);
    // Remove unwanted whitespace.
    $convertedToArray = array_map('trim', $convertedToArray);

    $resultArray = [];
    foreach ($convertedToArray as $convertValue) {
      if (strpos($convertValue, '|') !== FALSE) {
        $valueArray = explode("|", $convertValue);
        $resultArray[trim($valueArray[0])] = trim($valueArray[1]);
      }
      else {
        $resultArray[] = $convertValue;
      }
    }
    return $resultArray;
  }

}
