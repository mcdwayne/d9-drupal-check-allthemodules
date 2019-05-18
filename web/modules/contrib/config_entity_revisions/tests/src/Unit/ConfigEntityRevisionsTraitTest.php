<?php

namespace Drupal\Tests\config_entity_revisions\Unit;

use Drupal\Tests\UnitTestCase;

class ConfigEntityRevisionsTraitTest extends UnitTestCase {

  /**
   * Test that we can set and get a revision ID using the trait.
   *
   * @test
   */
  public function canSetAndGetARevisionID() {
    $instance = new TraitTest;
    $instance->updateLoadedRevisionId(704);

    $this->assertEquals(704, $instance->getRevisionId());
  }
}

class TraitTest {
  use \Drupal\config_entity_revisions\ConfigEntityRevisionsConfigTrait;
}