<?php

namespace Drupal\Tests\term_split\Kernel\TestDoubles;

use Drupal\taxonomy\TermInterface;
use Drupal\term_split\TermSplitterInterface;

class TermSplitterDummy implements TermSplitterInterface {

  /**
   * {@inheritdoc}
   */
  public function splitInTo(TermInterface $sourceTerm, $target1, $target2, array $target1Nids, array $target2Nids) {
    // Deliberately left empty, because dummies don't do anything.
  }

}
