<?php

namespace Drupal\taxonomy_rollup\Services;

/**
 * Interface for the TaxonomyRollupServiceInterface.
 */
interface TaxonomyRollupServiceInterface {

  /**
   * Method to get a Term ID from its Vocabulary ID and Name.
   *
   * Searches the specified vocabulary for a term with the name specified.
   * The terms within the vocabulary should have unique names. If an ambiguous
   * term is found -1 is returned. If the term is not found at all -1 is
   * returned. These should be logged.
   *
   * @param string $vid
   *   The vocabulary ID.
   * @param string $term_name
   *   The term name to search for.
   *
   * @return int
   *   The term ID or -1 if Term not found or ambiguous,
   */
  public function getTidFromName($vid, $term_name);

  /**
   * Method to get a rolled up term from a child term name.
   *
   * Searches the specified vocabulary for a term with the name specified.
   * If found, then traverse up the vocabulary tree to the specified height
   * and return the Term of the desired parent.
   *
   * @param string $vid
   *   The vocabulary ID.
   * @param string $term_name
   *   The term name to search for.
   * @param int $maxHeight
   *   The maximum number of levels to traverse up (or -1 to travel to the top).
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   The rolled up Term object or NULL if not found.
   */
  public function getRollupTermByTermName($vid, $term_name, $maxHeight = -1);

  /**
   * Method to get the name of a rolled up term from a child term name.
   *
   * See the method getRollupTermByTermName within this class as it performs
   * the same actions, just returning the name rather than the term.
   *
   * @param string $vid
   *   The vocabulary ID.
   * @param string $term_name
   *   The term name to search for.
   * @param int $maxHeight
   *   The maximum number of levels to traverse up (or -1 to travel to the top).
   *
   * @return string
   *   The rolled up term name (or the original term name on error condition).
   */
  public function getRollupTermNameByTermName($vid, $term_name, $maxHeight = -1);

  /**
   * Method to get the name of a rolled up term from a term id.
   *
   * See the method getRollupTermNameByTermName within this class as it performs
   * the same actions, using the term id rather than the term name.
   *
   * @param int $tid
   *   The term ID.
   * @param int $maxHeight
   *   The maximum number of levels to traverse up (or -1 to travel to the top).
   *
   * @return string
   *   The rolled up term name (or the original term name on error condition).
   */
  public function getRollupNameByTid($tid, $maxHeight = -1);

  /**
   * Method to get the tid of a rolled up term from a term id.
   *
   * See the method getRollupNameByTid within this class as it performs
   * the same actions, just returning the tid rather than the term name.
   *
   * @param int $tid
   *   The term ID.
   * @param int $maxHeight
   *   The maximum number of levels to traverse up (or -1 to travel to the top).
   *
   * @return int
   *   The rolled up term id (or the original term id on error condition).
   */
  public function getRollupTidByTid($tid, $maxHeight = -1);

  /**
   * Method to get the Term of a rolled up term from a term id.
   *
   * See the method getRollupTidByTid within this class as it performs
   * the same actions, just returning the Term rather than the term id.
   *
   * @param int $tid
   *   The term ID.
   * @param int $maxHeight
   *   The maximum number of levels to traverse up (or -1 to travel to the top).
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   The rolled up term (or NULL on error condition).
   */
  public function getRollupTermByTid($tid, $maxHeight = -1);

}
