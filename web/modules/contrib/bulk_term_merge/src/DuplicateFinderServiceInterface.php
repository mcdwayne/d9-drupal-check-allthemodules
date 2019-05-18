<?php

namespace Drupal\bulk_term_merge;

/**
 * Interface DuplicateFinderServiceInterface.
 */
interface DuplicateFinderServiceInterface {

  /**
   * Finds duplicate terms in a vocabulary.
   *
   * @param string $vid
   *   Vocabulary ID to retrieve terms for.
   *
   * @return array $duplicates
   *   The array of term names that are duplicates.
   */
  public function findDuplicates(string $vid);

  /**
   * Gets Term IDs.
   *
   * @param string $name
   *   Term name to search for.
   * @param string $vid
   *   Vocabulary ID to filter terms by.
   *
   * @return array $ids
   *   The array of term ids.
   */
  public function getTermIds(string $name, string $vid);

}
