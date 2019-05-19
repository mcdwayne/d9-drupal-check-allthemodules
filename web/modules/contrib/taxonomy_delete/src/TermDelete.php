<?php

namespace Drupal\taxonomy_delete;

/**
 * Class TermDelete.
 *
 * @package Drupal\taxonomy_delete
 */
class TermDelete {

  /**
   * Delete terms by Vocabulary.
   *
   * @param string $vid
   *   The Vocabulary from which the Terms needs to be deleted.
   *
   * @return array
   *   An array of terms which gets deleted.
   */
  public function deleteTermByVid($vid) {
    $terms = [];
    $controller = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $tree = $controller->loadTree($vid);
    foreach ($tree as $term) {
      $terms[] = $term->tid;
    }
    $entities = $controller->loadMultiple($terms);
    $controller->delete($entities);
    return count($terms);
  }

}
