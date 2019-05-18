<?php

namespace Drupal\conflict;

interface ConflictManagerInterface {

  /**
   * @return bool
   *  TRUE if condition defined in services applies on it else FALSE.
   */
  public function applies();

  /**
   * @param \Drupal\conflict\ConflictResolverInterface $resolver
   */
  public function addConflictResolver(ConflictResolverInterface $resolver);

  /**
   * @param \Drupal\conflict\ConflictAncestorResolverInterface $resolver
   */
  public function addAncestorResolver(ConflictAncestorResolverInterface $resolver);

}
