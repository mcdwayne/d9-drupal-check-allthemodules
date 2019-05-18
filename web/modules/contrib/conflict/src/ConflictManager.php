<?php

namespace Drupal\conflict;

use Drupal\Core\Entity\RevisionableInterface;

class ConflictManager {

  protected $resolvers = [];

  /**
   * @param \Drupal\conflict\ConflictResolverInterface $resolver
   */
  public function addConflictResolver(ConflictResolverInterface $resolver) {
    $this->resolvers[] = $resolver;
  }

  /**
   * @param \Drupal\Core\Entity\RevisionableInterface $revision1
   * @param \Drupal\Core\Entity\RevisionableInterface $revision2
   * @param \Drupal\Core\Entity\RevisionableInterface $revision3
   *
   * @return mixed
   */
  public function resolveSimpleMerge(RevisionableInterface $revision1, RevisionableInterface $revision2, RevisionableInterface $revision3) {
    foreach ($this->resolvers as $resolver) {
      if ($resolver->applies()) {
        return $resolver->merge($revision1, $revision2, $revision3);
      }
    }
  }

}
