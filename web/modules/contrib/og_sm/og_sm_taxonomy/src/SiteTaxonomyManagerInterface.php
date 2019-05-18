<?php

namespace Drupal\og_sm_taxonomy;

use Drupal\node\NodeInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Interface for site taxonomy manager classes.
 */
interface SiteTaxonomyManagerInterface {

  /**
   * Get a list of vocabulary names that have the OG group audience field.
   *
   * @return string[]
   *   Vocabulary names (labels) keyed by their machine name.
   */
  public function getSiteVocabularyNames();

  /**
   * Get all vocabulary objects that have the OG group audience field.
   *
   * @return \Drupal\taxonomy\VocabularyInterface[]
   *   Vocabulary objects keyed by their machine name.
   */
  public function getSiteVocabularies();

  /**
   * Fetches an array vocabularies referenced in an array query conditions.
   *
   * This helper function will loop recursively through the conditions and return
   * an array of referenced vocabularies.
   *
   * @param string[] $table_aliases
   *   An array of taxonomy table aliases.
   * @param array $conditions
   *   An array of query conditions.
   * @param \Drupal\taxonomy\VocabularyInterface[] $vocabularies
   *   (optional) An array of vocabulary objects.
   *
   * @return \Drupal\taxonomy\VocabularyInterface[]
   *   An array of vocabulary objects.
   */
  public function getSiteVocabulariesFromConditions(array $table_aliases, array $conditions, array $vocabularies = []);

  /**
   * Check if a given taxonomy vocabulary has the OG group audience field.
   *
   * @param string $name
   *   The vocabulary name.
   *
   * @return bool
   *   Whether or not this is a site vocabulary.
   */
  public function isSiteVocabulary($name);

  /**
   * Resets the weight for all site terms for a given vocabulary.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site for which the terms should be reset.
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   The vocabulary for which the terms should be reset.
   */
  public function resetTermWeights(NodeInterface $site, VocabularyInterface $vocabulary);

}
