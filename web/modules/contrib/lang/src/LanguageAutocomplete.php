<?php

/**
 * @file
 * Contains \Drupal\lang\LanguageAutocomplete.
 */

namespace Drupal\lang;

/**
 * Defines a helper class to get user autocompletion results.
 */
class LanguageAutocomplete {

  /**
   * Get matches for the autocompletion of languages.
   *
   * @param string $string
   *   The string to match for languages.
   *
   * @return array
   *   An array containing the matching languages.
   */
  public function getMatches($string) {
    $matches = array();
    if ($string) {
      $languages = getLanguageOptions();
      foreach ($languages as $langcode => $language) {
        if (strpos(drupal_strtolower($language), drupal_strtolower($string)) !== FALSE) {
          $matches[] = array('value' => $language, 'label' => $language);
        }
      }
    }
    return $matches;
  }
}
