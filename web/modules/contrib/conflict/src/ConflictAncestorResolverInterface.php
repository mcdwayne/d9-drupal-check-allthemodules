<?php

namespace Drupal\conflict;

use Fhaculty\Graph\Graph;
use Drupal\Core\Entity\RevisionableInterface;

/**
 * Provides an interface for defining LCA resolver entities.
 *
 * @ingroup conflict
 */
interface ConflictAncestorResolverInterface {

  /**
   * @return bool
   *  TRUE if condition defined in services applies on it else FALSE.
   */
  public function applies();

  /**
   * Resolves conflicts between different revisions.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision1
   * @param \Drupal\Core\Entity\RevisionableInterface $revision2
   * @param \Fhaculty\Graph\Graph|NULL $graph
   *
   * @return mixed
   */
  public function resolve(RevisionableInterface $revision1,RevisionableInterface $revision2, Graph $graph = NULL);

}
