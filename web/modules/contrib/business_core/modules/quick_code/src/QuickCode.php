<?php

namespace Drupal\quick_code;

use Drupal\Component\Transliteration\PhpTransliteration;

/**
 * Implements pinyin short code.
 */
class QuickCode extends PhpTransliteration {

  /**
   * {@inheritdoc}
   */
  public function transliterate($string, $langcode = 'en', $unknown_character = '?', $max_length = NULL) {
    $result = '';
    $length = 0;
    // Split into Unicode characters and transliterate each one.
    foreach (preg_split('//u', $string, 0, PREG_SPLIT_NO_EMPTY) as $character) {
      $code = self::ordUTF8($character);
      if ($code != -1) {
        $to_add = $this->replace($code, $langcode, $unknown_character);

        // Check if this exceeds the maximum allowed length.
        if (isset($max_length)) {
          $length += 1;
          if ($length > $max_length) {
            return $result;
          }
        }

        $result .= $to_add[0];
      }

    }

    return $result;
  }

}
