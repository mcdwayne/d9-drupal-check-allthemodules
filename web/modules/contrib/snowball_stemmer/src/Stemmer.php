<?php

namespace Drupal\snowball_stemmer;

use Drupal\Core\Language\LanguageInterface;

/**
 * Service wrapper class for stemmer.
 */
class Stemmer {

  /**
   * Language stemmer classes.
   *
   * @var \Wamania\Snowball\Stem[]
   */
  protected $stemmers;

  /**
   * Current stemming language.
   *
   * @var string
   */
  protected $language;

  /**
   * Temporary storage for lookups.
   *
   * @var string[]
   */
  protected $cache;

  /**
   * Language keyed array of overridden word arrays.
   *
   * @var array
   */
  protected $overrides;

  /**
   * Set language.
   *
   * @param string $language
   *   Two character language code.
   *
   * @return bool
   *   True if able to set, false if not supported.
   */
  public function setLanguage($language) {
    $supported = [
      'da' => 'Danish',
      'nl' => 'Dutch',
      'en' => 'English',
      'fr' => 'French',
      'de' => 'German',
      'it' => 'Italian',
      'no' => 'Norwegian',
      'pt' => 'Portuguese',
      'ro' => 'Romanian',
      'ru' => 'Russian',
      'es' => 'Spanish',
      'sv' => 'Swedish',
    ];

    if (isset($supported[$language])) {
      $this->language = $language;

      if (empty($this->stemmers[$language])) {
        $class = 'Wamania\\Snowball\\' . $supported[$language];
        $this->stemmers[$language] = new $class();
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Set overridden strings not to be automatically stemmed and return values.
   *
   * @param array $overrides
   *   Array with key as overridden string and 'stemmed' return string as value.
   * @param string $language
   *   Language code. Default will apply to all languages, but an override with
   *   language will take preference.
   */
  public function setOverrides($overrides, $language = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    $this->overrides[$language] = $overrides;
  }

  /**
   * Check for an overridden string.
   *
   * @param string $word
   *   Word to be checked.
   *
   * @return bool|string
   *   The value to use if overridden. FALSE if not an exception.
   */
  public function hasOverride($word) {
    if (isset($this->overrides[$this->language][$word])) {
      return $this->overrides[$this->language][$word];
    }

    if (isset($this->overrides[LanguageInterface::LANGCODE_NOT_SPECIFIED][$word])) {
      return $this->overrides[LanguageInterface::LANGCODE_NOT_SPECIFIED][$word];
    }

    return FALSE;
  }

  /**
   * Stem a word.
   *
   * @param string $word
   *   Word to stem.
   *
   * @return string
   *   Stemmed word.
   *
   * @throws \Drupal\snowball_stemmer\LanguageNotSetException
   *   If the language has not been set with self::setLanguage().
   */
  public function stem($word) {
    if (empty($this->language)) {
      throw new LanguageNotSetException('Stemmer has no language set.');
    }

    if ($override = $this->hasOverride($word)) {
      return $override;
    }

    if (!isset($this->cache[$this->language][$word])) {
      try {
        $this->cache[$this->language][$word] = $this->stemmers[$this->language]->stem($word);
      }
      catch (\Exception $e) {
        // Class throws a standard exception if the string is not UTF8.
        watchdog_exception('snowball_stemmer', $e);
        // Be nice, at least leave the word alone.
        $this->cache[$this->language][$word] = $word;
      }
    }

    return $this->cache[$this->language][$word];
  }

}
