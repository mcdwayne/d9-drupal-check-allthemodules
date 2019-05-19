<?php

namespace Drupal\term_node;

use Drupal\taxonomy\Entity\Term;

interface TermResolverInterface extends ResolverInterface {

  /**
   * Gets the id of the node referenced by the term.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *  The term that have the content reference field.
   *
   * @return int
   */
  public function getReferencedId(Term $term);

}
