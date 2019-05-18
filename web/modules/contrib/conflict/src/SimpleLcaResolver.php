<?php

namespace Drupal\conflict;

use Drupal\Core\Entity\RevisionableInterface;
use Fhaculty\Graph\Graph;

class SimpleLcaResolver implements ConflictAncestorResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function applies() {
    return TRUE;
  }

  /**
   * Finds the smallest revision id and calculates it's parent
   *
   * @param RevisionableInterface $revision1
   * @param RevisionableInterface $revision2
   *
   * @return int parent of both revisions
   */
  public function resolve(RevisionableInterface $revision1, RevisionableInterface $revision2, Graph $graph = NULL) {
    // Calculating revision ID from revision object.
    $revid1 = $revision1->getRevisionId();
    $revid2 = $revision2->getRevisionId();
    if ($revid1 < $revid2) {
      return $revid1-1;
    }
    return $revid2-1;
  }

}
