<?php

namespace Drupal\conflict;

use Drupal\Core\Entity\RevisionableInterface;

class SimpleMergeResolver implements ConflictResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function applies() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function merge(RevisionableInterface $revision1, RevisionableInterface $revision2, RevisionableInterface $revision3) {
    $revid1 = $revision1->getRevisionId();
    $revid2 = $revision2->getRevisionId();
    $revid3 = $revision3->getRevisionId();
    return max($revid1, $revid2, $revid3);
  }

}
