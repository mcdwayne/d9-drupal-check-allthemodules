<?php

namespace Drupal\views_restricted_simple;

class ViewsRestrictedSimple {

  /**
   * @param string $patternString
   *
   * @return array
   */
  public static function validatePatternString($patternString) {
    $patterns = self::parsePatternString($patternString);
    $errors = [];
    foreach (array_values($patterns) as $i => $pattern) {
      if (preg_match("#$pattern#u", NULL) === NULL) {
        $errors[] = t('Error in pattern #@i', ['@i' => $i + 1]);
      }
    }
    return $errors;
  }

  /**
   * @param string $patternString
   *
   * @return string[]
   */
  public static function parsePatternString($patternString) {
    $patterns = [];
    foreach (preg_split('/(\r\n|\r|\n)/u', $patternString) as $line) {
      list($line) = explode('#', $line);
      $line = trim($line);
      $patterns[] = $line;
    }
    return $patterns;
  }

}
