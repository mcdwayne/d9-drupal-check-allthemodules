<?php

namespace Drupal\conflict;

use Drupal\Core\Entity\RevisionableInterface;

interface ConflictResolverInterface {

  /**
   * @return bool
   *  TRUE if condition defined in services applies on it else FALSE.
   */
  public function applies();

  /**
   * Merges three revisons.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision1
   * @param \Drupal\Core\Entity\RevisionableInterface $revision2
   * @param \Drupal\Core\Entity\RevisionableInterface $revision3
   *
   * @return mixed
   *   Last created revision's Id.
   */
  public function merge(RevisionableInterface $revision1, RevisionableInterface $revision2, RevisionableInterface $revision3);

}
