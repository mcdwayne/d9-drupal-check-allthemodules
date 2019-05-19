<?php

namespace Drupal\straw;

use Drupal\taxonomy\Entity\Term;

/**
 * Stores terms which have been created by Straw but not yet saved by Drupal.
 */
class NewTermStorage {

  /**
   * The term storage.
   *
   * This is stored as an array of vocabulary names, each of which contains an
   * array with keys of the term hierarchy and values of the created Term object
   * which will eventually get saved.
   *
   * @var array
   */
  protected $termStorage;

  /**
   * Gets a term from the storage, or NULL if it doesn't exist.
   *
   * @param string $bundle
   *   The vocabulary in which to check for the term.
   * @param string $tree_path
   *   A string containing the hierarchy of what term to look up.
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   The stored term.
   */
  public function get($bundle, $tree_path) {
    return $this->termStorage[$bundle][$tree_path] ?? NULL;
  }

  /**
   * Adds a new term to the storage.
   *
   * @param string $bundle
   *   The vocabulary the term will be a part of.
   * @param string $tree_path
   *   A string containing the hierarchy of where the term will exist once it is
   *   created.
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The newly created term.
   */
  public function set($bundle, $tree_path, Term $term) {
    $this->termStorage[$bundle][$tree_path] = $term;
  }

}
