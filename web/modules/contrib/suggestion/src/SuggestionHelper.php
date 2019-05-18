<?php

namespace Drupal\suggestion;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides helper methods for suggestions.
 */
class SuggestionHelper {
  const C = 8;
  const MAX_SCORE = 100;
  const MAX_DEPTH = 4;
  const MIN_DELTA = 2;
  const MIN_SCORE = 1;
  const EXP = 0.5;

  /**
   * Search the form recursivley for the field and add the autocomplete route.
   *
   * @param array $form
   *   Part or all of a form render array.
   * @param string $field_name
   *   The field name to search for.
   * @param string $level
   *   The current recursion level.
   *
   * @return array
   *   An array of ngrams keys.
   */
  public static function alterElement(array &$form, $field_name, $level = 1) {
    $suxs = FALSE;
    $types = [
      'search',
      'textfield',
    ];
    if ($level > self::MAX_DEPTH) {
      return FALSE;
    }
    foreach ($form as $key => &$element) {
      if ($key == $field_name && !empty($form[$key]['#type']) && in_array($form[$key]['#type'], $types)) {
        $form[$key]['#autocomplete_route_name'] = 'suggestion.autocomplete';
        $suxs = TRUE;
      }
      elseif (is_array($element)) {
        $suxs = self::alterElement($element, $field_name, $level + 1);
      }
      if ($suxs) {
        break;
      }
    }
    if ($suxs && $level == 1) {
      $form['#submit'][] = 'suggestion_surfer_submit';
    }
    return $suxs;
  }

  /**
   * Transform a string to an array.
   *
   * @param string $txt
   *   The string to process.
   *
   * @return array
   *   An array of atoms.
   */
  public static function atomize($txt = '') {
    $atoms = [];
    $stopwords = self::getStops('stopwords');

    foreach (preg_split('/\s+/', $txt) as $atom) {
      if (!empty($stopwords[$atom])) {
        continue;
      }
      $atoms[$atom] = $atom;
    }
    return $atoms;
  }

  /**
   * Calculate the ngram's density.
   *
   * @param int $src
   *   The ngram source.
   * @param int $atoms
   *   The number of atoms in the ngram.
   * @param int $qty
   *   The submission count.
   *
   * @return float
   *   The suggestion's score.
   */
  public static function calculateDensity($src = 0, $atoms = 1, $qty = 0) {
    $score = intval($src) * self::C;

    return (float) $score + self::getDelta((self::MAX_SCORE - $score), intval(pow(($atoms + $qty), self::EXP)));
  }

  /**
   * Configuration get wrapper.
   *
   * @param string $key
   *   The field name.
   *
   * @return mixed
   *   The value of the supplied field.
   */
  public static function getConfig($key = '') {
    $cfg = &drupal_static(__CLASS__ . '_' . __FUNCTION__, \Drupal::configFactory()->get('suggestion.config'));

    return $key ? $cfg->get($key) : (object) $cfg->get();
  }

  /**
   * Build an array of language codes.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   An array of language codes.
   */
  public static function getLancodes($langcode = '') {
    $langcodes = [
      LanguageInterface::LANGCODE_NOT_APPLICABLE,
      LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];
    if ($langcode && !in_array($langcode, $langcodes)) {
      $langcodes[] = $langcode;
    }
    return $langcodes;
  }

  /**
   * Stopword get wrapper.
   *
   * @param string $key
   *   The field name.
   *
   * @return mixed
   *   The value of the supplied field.
   */
  public static function getStops($key = '') {
    $cfg = &drupal_static(__CLASS__ . '_' . __FUNCTION__, \Drupal::configFactory()->get('suggestion.stopword'));

    return $key ? $cfg->get($key) : (object) $cfg->get();
  }

  /**
   * Create a suggestion index from content titles.
   *
   * @param int $last_nid
   *   The last node ID processed.
   * @param int $limit
   *   The query limit.
   */
  public static function index($last_nid = 0, $limit = PHP_INT_MAX) {
    $count = &drupal_static(__CLASS__ . '_' . __FUNCTION__ . '_count', 0);
    $nid = &drupal_static(__CLASS__ . '_' . __FUNCTION__ . '_nid', 0);

    self::setConfig('synced', TRUE);

    if (!$last_nid) {
      SuggestionStorage::deleteContentSuggestion();
      SuggestionStorage::updateContentSrc();
    }
    $data = SuggestionStorage::getTitles($last_nid, $limit);

    foreach ($data as $obj) {
      $nid = $obj->nid;
      $count += self::insert($obj->title, $obj->langcode, SuggestionStorage::CONTENT_BIT);
    }
  }

  /**
   * Add a suggestion.
   *
   * @param string $txt
   *   The title to index.
   * @param string $langcode
   *   The language code.
   * @param int $src
   *   The bits to OR with the current bitmap.
   * @param int $qty
   *   Default quantity.
   *
   * @return int
   *   The number of suggestions inserted.
   */
  public static function insert($txt, $langcode, $src = SuggestionStorage::CONTENT_BIT, $qty = NULL) {
    $count = 0;
    $max = self::getConfig('max');
    $txt = self::tokenize($txt, self::getConfig('min'));

    if (!$txt) {
      return 0;
    }
    $atoms = self::atomize($txt);

    foreach (array_keys(self::ngrams($atoms)) as $ngram) {
      if (Unicode::strlen($ngram) > $max) {
        continue;
      }
      // UTF-8 safe word count.
      $count = count(preg_split('/\s/', $ngram));
      $qty = is_numeric($qty) ? $qty + 1 : SuggestionStorage::getNgramQty($ngram, $langcode) + 1;
      $src = SuggestionStorage::getBitmap($ngram, $src, $langcode);

      $key = [
        'langcode' => $langcode,
        'ngram'    => $ngram,
      ];
      $fields = [
        'atoms'    => $count,
        'density'  => self::calculateDensity($src, $count, $qty),
        'qty'      => $qty,
        'src'      => $src,
      ];
      SuggestionStorage::mergeSuggestion($key, $fields);

      $count++;
    }
    return $count;
  }

  /**
   * Build a set of ngrams from the set of atoms.
   *
   * @param array $atoms
   *   An array of strings.
   *
   * @return array
   *   An array of ngrams keys.
   */
  public static function ngrams(array $atoms = []) {
    $max = self::getConfig('atoms_max');
    $min = self::getConfig('atoms_min');
    $ngrams = [];

    $count = count($atoms) - $min;

    for ($i = 0; $i <= $count; $i++) {
      for ($j = $min; $j <= $max; $j++) {
        $ngrams[implode(' ', array_slice($atoms, $i, $j))] = 1;
      }
    }
    $atoms = array_reverse($atoms);

    for ($i = 0; $i <= $count; $i++) {
      for ($j = $min; $j <= $max; $j++) {
        $ngrams[implode(' ', array_slice($atoms, $i, $j))] = 1;
      }
    }
    return $ngrams;
  }

  /**
   * Configuration get wrapper.
   *
   * @param string $key
   *   The field name.
   * @param mixed $val
   *   The field value.
   *
   * @return mixed
   *   The value of the supplied field.
   */
  public static function setConfig($key = '', $val = NULL) {
    $cfg = &drupal_static(__CLASS__ . '_' . __FUNCTION__, \Drupal::configFactory()->getEditable('suggestion.config'));

    return $key ? $cfg->set($key, $val)->save() : NULL;
  }

  /**
   * Configuration get wrapper.
   *
   * @param string $key
   *   The field name.
   * @param mixed $val
   *   The field value.
   *
   * @return mixed
   *   The value of the supplied field.
   */
  public static function setStops($key = '', $val = NULL) {
    $cfg = &drupal_static(__CLASS__ . '_' . __FUNCTION__, \Drupal::configFactory()->getEditable('suggestion.stopword'));

    return $key ? $cfg->set($key, $val)->save() : NULL;
  }

  /**
   * Build an array of defalut options from bitmaps.
   *
   * @param int $src
   *   The ngrams source bitmap.
   *
   * @return array
   *   An array of default option.
   */
  public static function srcBits($src = 0) {
    $bits = [];

    if (intval($src) <= 0) {
      return [0];
    }
    foreach (array_keys(SuggestionStorage::getSrcOptions()) as $bit) {
      if ($bit & $src) {
        $bits[] = $bit;
      }
    }
    return $bits;
  }

  /**
   * Convert an array of submitted form options to a bitmap.
   *
   * @param array $bits
   *   An array of form options.
   *
   * @return int
   *   The option values bitwise OR.
   */
  public static function optionBits(array $bits = []) {
    $src = 0;

    foreach ($bits as $bit) {
      $src |= intval($bit);
    }
    return $src;
  }

  /**
   * Tokenize the text into space separated lowercase strings.
   *
   * @param string $txt
   *   The text to process.
   * @param int $min
   *   The minimum number of characters in a token.
   *
   * @return string
   *   The tokenized string.
   */
  public static function tokenize($txt = '', $min = 4) {
    $min--;

    $regx = [
      '/[^\w]+/su'                   => ' ',
      '/\b(\w{1,' . $min . '})\b/su' => '',
      '/\s\s+/su'                    => ' ',
      '/^\s+|\s+$/su'                => '',
    ];
    return preg_replace(array_keys($regx), array_values($regx), Unicode::strtolower(trim($txt)));
  }

  /**
   * Build an array of content types used in auto-complete.
   *
   * @return array
   *   An array of enabled content types.
   */
  public static function types() {
    $types = &drupal_static(__CLASS__ . '_' . __FUNCTION__, NULL);

    if (is_array($types)) {
      return $types;
    }
    foreach (self::getConfig('types') as $type => $status) {
      if ($status) {
        $types[] = $type;
      }
    }
    return $types ? $types : [];
  }

  /**
   * Calculate the ngram's density.
   *
   * @param string $ngram
   *   The ngram to update.
   * @param int $src
   *   The ngram source.
   * @param string $langcode
   *   The language code.
   *
   * @return object
   *   A Merge object.
   */
  public static function updateSrc($ngram = '', $src = 0, $langcode = '') {
    $obj = SuggestionStorage::getSuggestion($ngram);

    $key = [
      'langcode' => $langcode,
      'ngram'    => $ngram,
    ];
    $fields = [
      'atoms'   => $obj->atoms,
      'density' => self::calculateDensity($src, $obj->atoms, $obj->qty),
      'qty'     => $obj->qty,
      'src'     => $src,
    ];
    return SuggestionStorage::mergeSuggestion($key, $fields);
  }

  /**
   * Estimate the performance.
   *
   * @param float $delta
   *   The current performance remainder.
   * @param int $n
   *   The summation N - 1.
   *
   * @return float
   *   The suggestion's remainder summation.
   */
  protected static function getDelta($delta, $n) {
    if ($delta < self::MIN_DELTA || !$n) {
      return 0;
    }
    $x = pow($delta, self::EXP);

    return $x + self::getDelta(($delta - $x), ($n - 1));
  }

}
