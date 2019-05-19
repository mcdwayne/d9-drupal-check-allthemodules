<?php

namespace Drupal\term_node;

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\taxonomy\Entity\Term;

class NodeResolver implements NodeResolverInterface {

  /**
   * @inheritDoc
   */
  public function getPath($path, $nid) {
    // Get the tid of a referencing term.
    if ($tid = $this->getReferencedBy($nid)) {
      try {
        if ($term = Term::load($tid)) {
          return $term->toUrl()->toString();
        }
      } catch (EntityMalformedException $e) {
        // Just return the original path on error.
      }
    }

    return $path;
  }

  /**
   * @inheritDoc
   */
  public function getReferencedBy($nid) {
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('field_term_node', $nid)
    ;
    $tids = $query->execute();
    if (count($tids) > 0) {
      return reset($tids);
    }

    return FALSE;
  }
}
