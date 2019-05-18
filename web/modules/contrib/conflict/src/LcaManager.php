<?php

namespace Drupal\conflict;

use Fhaculty\Graph\Graph;
use Drupal\Core\Entity\RevisionableInterface;

class LcaManager {

  protected $resolvers = [];

  /**
   *
   * @param ConflictAncestorResolverInterface $resolver
   */
  public function addLcaResolver(ConflictAncestorResolverInterface $resolver) {
    $this->resolvers[] = $resolver;
  }

  /**
   * @param RevisionableInterface $revision1
   * @param RevisionableInterface $revision2
   * @param \Fhaculty\Graph\Graph $graph
   *
   * @return int revision_ID
   */
  public function resolveLowestCommonAncestor(RevisionableInterface $revision1, RevisionableInterface $revision2, Graph $graph = NULL) {
    foreach ($this->resolvers as $resolver) {
      if ($resolver->applies()) {
        return $resolver->resolve($revision1, $revision2, $graph);
      }
    }
  }

}
