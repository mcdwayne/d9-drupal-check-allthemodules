<?php

namespace Drupal\Tests\feeds_para_mapper\Functional;



/**
 * Test Importing.
 * @group Feeds Paragraphs
 */
class ImportingTest extends FeedsParaMapperTestBase {
  protected function setUp()
  {
    parent::setUp();
  }

  public function testThings(){
    $this->drupalGet('http://localhost/admin/structure/feeds');
    debug('my data');
  }
}