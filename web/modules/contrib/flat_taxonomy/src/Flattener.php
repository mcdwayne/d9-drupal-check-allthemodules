<?php

/**
 * @file
 * Service to flatten a vocabulary.
 */

namespace Drupal\flat_taxonomy;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides method to flatten a vocabulary's terms.
 *
 * @package Drupal\flat_taxonomy
 */
class Flattener {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * @var \Drupal\taxonomy\TermStorage
   */
  protected $taxonomyTermStorage;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_manager
   */
  public function __construct(EntityTypeManager $entity_manager) {
    $this->entityManager = $entity_manager;
    $this->taxonomyTermStorage = $this->entityManager->getStorage('taxonomy_term');
  }

  /**
   * Flatten an entire vocabulary.
   *
   * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
   */
  public function flatten(Vocabulary $vocabulary) {
    $weight = 0;
    $tree = $this->taxonomyTermStorage->loadTree($vocabulary->id(), 0, 1, TRUE);

    foreach ($tree as $term) {
      $this->flatten_subtree($term, $weight);
    }
  }

  /**
   * Flatten a vocabulary subtree from the given term.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   * @param $weight
   */
  function flatten_subtree(Term $term, $weight) {
    // Update the given term.
    $term->weight = $weight++;
    $term->parent = 0;
    $term->save();

    // Get the subtree from the given term.
    $tree = $this->taxonomyTermStorage->loadTree($term->getVocabularyId(), $term->id(), 1, TRUE);

    // Recursively flatten each children subtree.
    foreach ($tree as $term) {
      $this->flatten_subtree($term, $weight);
    }
  }

}