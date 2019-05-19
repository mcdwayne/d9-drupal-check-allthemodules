<?php

namespace Drupal\sir_trevor\Tests\Unit\Plugin;

use Drupal\sir_trevor\Plugin\SirTrevorBlock;
use Drupal\Tests\UnitTestCase;

/**
 * @group SirTrevor
 */
class SirTrevorBlockPluginTest extends UnitTestCase {

  private function getDefinitionBase() {
    return [
      'id' => 'some_id',
      'provider' => 'some_module',
    ];
  }

  /**
   * @test
   * @expectedException \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @expectedExceptionMessage "some_id" must define "template".
   */
  public function definitionWithoutTemplate() {
    new SirTrevorBlock($this->getDefinitionBase());
  }
}
