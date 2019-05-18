<?php

namespace Drupal\disrupt_tools\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * TaxonomyHelpers.
 *
 * Service to make it easy to work with Taxonomy Term.
 */
class TaxonomyHelpers {
  /**
   * The term Storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  private $termStorage;

  /**
   * Entity_query to query Node's Code.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  private $queryFactory;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity, QueryFactory $query_factory) {
    $this->termStorage = $entity->getStorage('taxonomy_term');
    $this->queryFactory = $query_factory;
  }

  /**
   * Get all the siblings terms of a given taxonomy tid.
   *
   * @param int $tid
   *   Taxonomy tid.
   * @param int $max_depth
   *   The number of levels of the siblings tree to return.
   *   Leave NULL to return all levels.
   *
   * @return array|null
   *   An array of Drupal\taxonomy\Entity\Term that are the siblings
   *   of the taxonomy term $tid.
   */
  public function getSiblings($tid, $max_depth = 1) {
    $term = $this->termStorage->load($tid);

    if (!$term) {
      return NULL;
    }

    // Retrieve this term's direct parent to load all children.
    $parent = $this->getTopParent($tid);
    if (!empty($parent)) {

      // Check if we load from a top parent or
      // from nothing (so the given $tid is a top parent).
      $load_from = $parent->id();
      if ($term->id() == $parent->id()) {
        $load_from = 0;
      }

      // Load the flat tree.
      $flat_tree = $this->termStorage->loadTree($term->getVocabularyId(), $load_from, $max_depth, TRUE);

      return $flat_tree;
    }

    return NULL;
  }

  /**
   * Get the top parent term of given taxonomy term.
   *
   * @param int $tid
   *   Given tid to retrieve top parent.
   * @param Drupal\Core\Entity\EntityInterface $parent
   *   Current parent.
   *
   * @return Drupal\taxonomy\Entity\Term
   *   The parent Taxonomy term.
   */
  public function getTopParent($tid, EntityInterface $parent = NULL) {
    // Check it has parent.
    if ($parent = $this->termStorage->loadParents($tid)) {
      $parents_tid = array_keys($parent);
      $parent_tid = reset($parents_tid);
      $parent = reset($parent);

      // Check if it's a top parent, otherwise load until reach top.
      if ($parent_tid != 0) {
        $parent = $this->getTopParent($parent_tid, $parent);
      }
    }
    else {
      $parent = $this->termStorage->load($tid);
    }

    return $parent;
  }

  /**
   * Retrieve the depth of a given term id into his vocabulary.
   *
   * @param int $tid
   *   Taxonomy tid to get hierarchy level.
   *
   * @return int|null
   *   Depth of the given term id.
   */
  public function getDepth($tid) {
    $parents = [];
    $this->getParentRecursive($tid, $parents);
    return count($parents);
  }

  /**
   * Get all parent term of given taxonomy term.
   *
   * @param int $tid
   *   Given tid to retrieve all parent.
   *
   * @return array
   *   The parent Taxonomy term.
   */
  public function getParents($tid) {
    $parents = [];
    $this->getParentRecursive($tid, $parents);
    return $parents;
  }

  /**
   * Get the tree parents term of given taxonomy term by recursivity.
   *
   * @param int $tid
   *   Given tid to retrieve top parent.
   * @param array $parents
   *   The tree of parents, incremented by each iteration of the recusrive loop.
   *
   * @return Drupal\taxonomy\Entity\Term
   *   The parent Taxonomy term.
   */
  private function getParentRecursive($tid, array &$parents = []) {
    // Check it has parent.
    if ($parent = $this->termStorage->loadParents($tid)) {
      $parents_tid = array_keys($parent);
      $parent_tid = reset($parents_tid);

      // Check if it's a top parent, otherwise load until reach top.
      if ($parent_tid != 0) {
        $parents[] = $this->getParentRecursive($parent_tid, $parents);
      }
    }

    return $this->termStorage->load($tid);
  }

  /**
   * Converting a flat array of Drupal\taxonomy\Entity\Term into a nested tree.
   *
   * The $elements must be generated from Drupal\taxonomy\TermStorage::loadTree.
   *
   * @param array $elements
   *   Flat array of Drupal\taxonomy\Entity\Term.
   * @param int $parent
   *   Previous $parent Drupal\taxonomy\Entity\Term.
   *
   * @return array
   *   Nested array of Drupal\taxonomy\Entity\Term.
   */
  public function buildTree(array $elements, $parent = 0) {
    $branch = [];

    foreach ($elements as $element) {
      if ($element->parents[0] == $parent) {
        $children = $this->buildTree($elements, $element->id());
        if ($children) {
          $element->children = $children;
        }
        $branch[] = $element;
      }
    }

    return $branch;
  }

  /**
   * Finds all terms in a given vocabulary ID and filter them by conditions.
   *
   * @param int $vid
   *   Vocabulary ID to retrieve terms for.
   * @param int $parent
   *   The term ID under which to generate the tree.
   *   If 0, generate the tree for the entire vocabulary.
   * @param array $conditions
   *   Array of conditions to apply.
   * @param int $max_depth
   *   The number of levels of the siblings tree to return.
   *   Leave NULL to return all levels.
   *
   * @return object[]|\Drupal\taxonomy\TermInterface[]
   *   An array of term objects that are the children of the vocabulary $vid.
   */
  public function loadTreeBy($vid, $parent, array $conditions, $max_depth = 1) {
    $query = $this->queryFactory->get('taxonomy_term')
      ->condition('vid', $vid);
    foreach ($conditions as $field => $condition) {
      $query->condition($field, $condition);
    }

    // Load final data.
    $tids = $query->execute();

    $flat_tree = $this->termStorage->loadTree($vid, $parent, $max_depth, TRUE);
    foreach ($flat_tree as $key => $branch) {
      if (!in_array($branch->tid->value, $tids)) {
        unset($flat_tree[$key]);
      }
    }

    return $flat_tree;
  }

}
