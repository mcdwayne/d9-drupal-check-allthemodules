<?php

namespace Drupal\term_split;

use Drupal\taxonomy\TermInterface;

/**
 * Splits a term into two target terms.
 */
interface TermSplitterInterface {

  /**
   * Splits a source term into two new target terms.
   *
   * @param \Drupal\taxonomy\TermInterface $sourceTerm
   *   The term to split.
   * @param string $target1
   *   The name of the first target term.
   * @param string $target2
   *   The name of the second target term.
   * @param array $target1Nids
   *   The nids to migrate to the first target.
   * @param array $target2Nids
   *   The nids to migrate to the second target.
   */
  public function splitInTo(TermInterface $sourceTerm, $target1, $target2, array $target1Nids, array $target2Nids);

}
