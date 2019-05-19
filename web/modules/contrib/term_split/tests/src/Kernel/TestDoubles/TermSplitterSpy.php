<?php

namespace Drupal\Tests\term_split\Kernel\TestDoubles;

use Drupal\taxonomy\TermInterface;

class TermSplitterSpy extends TermSplitterDummy {

  private $splitCalls = [];

  /**
   * {@inheritdoc}
   */
  public function splitInTo(TermInterface $sourceTerm, $target1, $target2, array $target1Nids, array $target2Nids) {
    $this->splitCalls[] = [$sourceTerm, $target1, $target2, $target1Nids, $target2Nids];
    parent::splitInTo($sourceTerm, $target1, $target2, $target1Nids, $target2Nids);
  }

  public function splitCallCount() {
    return count($this->splitCalls);
  }

  public function getSplitCalls() {
    return $this->splitCalls;
  }

}
