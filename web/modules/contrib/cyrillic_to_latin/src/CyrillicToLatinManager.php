<?php

namespace Drupal\cyrillic_to_latin;

use Drupal\Core\StringTranslation\TranslationManager;

/**
 * Defines a chained translation implementation combining multiple translators.
 */
class CyrillicToLatinManager extends TranslationManager {

  /**
   * {@inheritdoc}
   */
  protected function doTranslate($string, array $options = []) {
    // If a NULL langcode has been provided, unset it.
    if (!isset($options['langcode']) && array_key_exists('langcode', $options)) {
      unset($options['langcode']);
    }

    // Merge in options defaults.
    $options = $options + [
      'langcode' => $this->defaultLangcode,
      'context' => '',
    ];
    $translation = $this->getStringTranslation($options['langcode'], $string, $options['context']);

    $config = \Drupal::configFactory()->get('cyrillic_to_latin.settings');
    if ($config->get('enabled')) {
      return $translation === FALSE ? $string : self::convertCyrillicToLatin($translation);
    }

    return $translation === FALSE ? $string : $translation;
  }

  /**
   * Converts cyrillic letters to latin.
   *
   * @param string $string
   *   A string containing the text to convert.
   *
   * @return string
   *   The converted string.
   */
  public static function convertCyrillicToLatin($string) {
    $cyrillic = [
      'њ', 'љ', 'а', 'б', 'в', 'г', 'д', 'ђ', 'e', 'ж', 'з', 'и', 'ј', 'к', 'л',
      'м', 'н', 'о', 'п', 'р', 'с', 'т', 'ћ', 'у', 'ф', 'х', 'ц', 'ч', 'џ', 'ш',
      'Њ', 'Љ', 'А', 'Б', 'В', 'Г', 'Д', 'Ђ', 'Е', 'Ж', 'З', 'И', 'Ј', 'К', 'Л',
      'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'Ћ', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Џ', 'Ш',
    ];

    $latin = [
      'nj', 'lj', 'a', 'b', 'v', 'g', 'd', 'đ', 'e', 'ž', 'z', 'i', 'j', 'k',
      'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'ć', 'u', 'f', 'h', 'c', 'č',
      'dž', 'š', 'Nj', 'Lj', 'A', 'B', 'V', 'G', 'D', 'Đ', 'E', 'Ž', 'Z', 'I',
      'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'Ć', 'U', 'F', 'H', 'C',
      'Č', 'Dž', 'Š',
    ];

    return str_replace($cyrillic, $latin, $string);
  }

}
