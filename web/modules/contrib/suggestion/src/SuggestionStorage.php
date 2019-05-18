<?php

namespace Drupal\suggestion;

/**
 * Database CRUD.
 */
class SuggestionStorage {

  // Source bitmaps.
  const CONTENT_BIT = 1;
  const DISABLED_BIT = 0;
  const PRIORITY_BIT = 4;
  const SURFER_BIT = 2;

  /**
   * Delete all content suggestions.
   *
   * @return object
   *   A Delete object.
   */
  public static function deleteContentSuggestion() {
    return db_delete('suggestion')->condition('src', self::CONTENT_BIT)->execute();
  }

  /**
   * Fetch all the suggestions.
   *
   * @param string $langcode
   *   The language code.
   * @param int $start
   *   The starting offset.
   * @param int $limit
   *   The query limit.
   *
   * @return array
   *   An array of suggestion objects.
   */
  public static function getAllSuggestions($langcode = '', $start = NULL, $limit = 100) {
    if (is_numeric($start) && intval($limit)) {
      return db_query_range("SELECT * FROM {suggestion} WHERE langcode = :langcode ORDER BY ngram ASC", $start, $limit, [':langcode' => $langcode])->fetchAll();
    }
    return db_query("SELECT * FROM {suggestion} WHERE langcode = :langcode ORDER BY ngram ASC", [':langcode' => $langcode])->fetchAll();
  }

  /**
   * Fetch a set of suggestions.
   *
   * @param string $ngram
   *   The search string.
   * @param int $atoms
   *   The number of atoms.
   * @param int $limit
   *   The query limit.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   An array of suggestions.
   */
  public static function getAutocomplete($ngram = '', $atoms = 0, $limit = 100, $langcode = '') {
    $args = [
      ':ngram'       => $ngram,
      ':atoms'       => (int) $atoms,
      ':langcodes[]' => SuggestionHelper::getLancodes($langcode),
    ];
    $stmt = "
      SELECT
        ngram,
        ngram
      FROM
        {suggestion}
      WHERE
        ngram LIKE :ngram
        AND src
        AND atoms <= :atoms
        AND langcode IN (:langcodes[])
      ORDER BY
        density DESC,
        ngram ASC,
        atoms ASC
    ";
    return db_query_range($stmt, 0, (int) $limit, $args)->fetchAllKeyed();
  }

  /**
   * Calculate the suggestion's bitmap.
   *
   * @param string $ngram
   *   The text to index.
   * @param int $src
   *   The bits to OR with the current bitmap.
   * @param string $langcode
   *   The language code.
   *
   * @return int
   *   The new bitmap value.
   */
  public static function getBitmap($ngram = '', $src = self::CONTENT_BIT, $langcode = '') {
    $args = [
      ':langcode' => $langcode,
      ':ngram'    => $ngram,
      ':src'      => (int) $src,
    ];
    return db_query("SELECT IFNULL(SUM(src), 0) | :src FROM {suggestion} WHERE ngram = :ngram AND langcode = :langcode", $args)->fetchField();
  }

  /**
   * Fetch an array of content types.
   *
   * @return array
   *   An array of content types.
   */
  public static function getContentTypes() {
    return db_query("SELECT DISTINCT(type), type FROM {node} ORDER BY type ASC")->fetchAllKeyed();
  }

  /**
   * Fetch the row count.
   *
   * @param string $langcode
   *   The language code.
   * @param string $ngram
   *   The text to search for.
   *
   * @return int
   *   The number of rows in the suggestion table.
   */
  public static function getCount($langcode, $ngram = '') {
    if ($ngram) {
      return db_query("SELECT COUNT(*) FROM {suggestion} WHERE langcode = :langcode AND ngram LIKE :ngram", [':langcode' => $langcode, ':ngram' => $ngram])->fetchField();
    }
    return db_query("SELECT COUNT(*) FROM {suggestion} WHERE langcode = :langcode", [':langcode' => $langcode])->fetchField();
  }

  /**
   * Fetch an array of priority suggestions.
   *
   * @param string $language
   *   The language code.
   *
   * @return array
   *   An array of suggestions.
   */
  public static function getKeywords($language) {
    return db_query("SELECT ngram FROM {suggestion} WHERE src & :src AND langcode = :langcode ORDER BY ngram ASC", [':src' => self::PRIORITY_BIT, ':langcode' => $language])->fetchCol();
  }

  /**
   * Fetch the quantity for the supplied ngram.
   *
   * @param string $ngram
   *   The ngram value.
   * @param string $langcode
   *   The language code.
   *
   * @return int
   *   The qty value for the supplied ngram.
   */
  public static function getNgramQty($ngram = '', $langcode = '') {
    return db_query("SELECT IFNULL(SUM(qty), 0) FROM {suggestion} WHERE ngram = :ngram AND langcode IN (:langcodes[])", [':ngram' => $ngram, ':langcodes[]' => SuggestionHelper::getLancodes($langcode)])->fetchField();
  }

  /**
   * Calculate a suggestion's score.
   *
   * @param array $atoms
   *   An array of strings.
   * @param string $langcode
   *   The language code.
   *
   * @return int
   *   The suggestion's score.
   */
  public static function getScore(array $atoms = [], $langcode = '') {
    $types = SuggestionHelper::types();

    if (!count($types)) {
      return 0;
    }
    $query = db_select('node__body', 'b');

    $query->fields('b', ['entity_id']);
    $query->join('node_field_data', 'n', 'n.nid = b.entity_id');
    $query->condition('n.status', 1);
    $query->condition('n.langcode', $langcode);
    $query->condition('n.type', $types, 'IN');

    foreach ($atoms as $atom) {
      $query->condition('b.body_value', '%' . db_like($atom) . '%', 'LIKE');
    }
    return count($atoms) ? count($query->execute()->fetchCol()) : 0;
  }

  /**
   * Build an array of source options.
   *
   * @return array
   *   An array of source options.
   */
  public static function getSrcOptions() {
    return [
      self::DISABLED_BIT => t('Disabled'),
      self::CONTENT_BIT  => t('Content'),
      self::SURFER_BIT   => t('Surfer'),
      self::PRIORITY_BIT => t('Priority'),
    ];
  }

  /**
   * Fetch the data for the suplied ngram.
   *
   * @param string $ngram
   *   The requested ngram.
   *
   * @return object
   *   An array of suggestion objects.
   */
  public static function getSuggestion($ngram = '') {
    return db_query("SELECT * FROM {suggestion} WHERE ngram = :ngram", [':ngram' => $ngram])->fetchObject();
  }

  /**
   * Fetch an array of node titles.
   *
   * @param int $nid
   *   The Node ID of the last node batched.
   * @param int $limit
   *   The query limit.
   *
   * @return array
   *   A node ID to title hash.
   */
  public static function getTitles($nid = 0, $limit = PHP_INT_MAX) {
    $args = [
      ':nid'     => (int) $nid,
      ':types[]' => SuggestionHelper::types(),
    ];
    $stmt = "
      SELECT
        nid,
        title,
        langcode
      FROM
        {node_field_data}
      WHERE
        status = 1
        AND nid > :nid
        AND type IN (:types[])
      ORDER BY
        nid ASC
    ";
    return count($args[':types[]']) ? db_query_range($stmt, 0, $limit, $args)->fetchAll() : [];
  }

  /**
   * Merge the supplied suggestion.
   *
   * @param array $key
   *   The suggestion key array.
   * @param array $fields
   *   The suggestion field array.
   *
   * @return object
   *   A Merge object.
   */
  public static function mergeSuggestion(array $key = [], array $fields = []) {
    return db_merge('suggestion')->key($key)->fields($fields)->execute();
  }

  /**
   * Search suggestions.
   *
   * @param string $ngram
   *   The requested ngram.
   * @param string $langcode
   *   The language code.
   * @param int $start
   *   The starting offset.
   * @param int $limit
   *   The query limit.
   *
   * @return array
   *   An array of suggestion objects.
   */
  public static function search($ngram, $langcode, $start = NULL, $limit = 100) {
    $args = [
      ':langcode' => $langcode,
      ':ngram'    => $ngram,
    ];
    if (is_numeric($start) && intval($limit)) {
      return db_query_range("SELECT * FROM {suggestion} WHERE langcode = :langcode AND ngram LIKE :ngram ORDER BY ngram ASC", $start, $limit, $args)->fetchAll();
    }
    return db_query("SELECT * FROM {suggestion} WHERE langcode = :langcode AND ngram LIKE :ngram ORDER BY ngram ASC", $args)->fetchAll();
  }

  /**
   * Truncate the suggestion table.
   *
   * @return object
   *   A new TruncateQuery object for this connection.
   */
  public static function truncateSuggestion() {
    return db_truncate('suggestion')->execute();
  }

  /**
   * Remove the content bit from the source bitmap.
   *
   * @return object
   *   An Update object.
   */
  public static function updateContentSrc() {
    return db_update('suggestion')
      ->expression('src', 'src & :src', [':src' => intval(self::PRIORITY_BIT | self::SURFER_BIT)])
      ->condition('src', self::CONTENT_BIT, '&')
      ->execute();
  }

}
