<?php

namespace Drupal\flipclock;

/**
 * Class FlipClockManager
 */
class FlipClockManager {

  /**
   * @return array
   */
  public static function getLanguages() {
    return [
      'ar-ar' => 'Arabic',
      'da-dk' => 'Danish',
      'de-de' => 'German',
      'en-us' => 'English',
      'es-es' => 'Spanish',
      'fi-fi' => 'Finnish',
      'fr-ca' => 'French',
      'it-it' => 'Italian',
      'lv-lv' => 'Latvian',
      'nl-be' => 'Dutch',
      'no-nb' => 'Norwegian',
      'pt-br' => 'Portugese',
      'ru-ru' => 'Russian',
      'sv-se' => 'Swedish',
    ];
  }

}