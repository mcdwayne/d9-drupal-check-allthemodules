<?php

namespace Drupal\clu\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the clu module.
 */
class CLUSessionTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "clu CLUSession's controller functionality",
      'description' => 'Test Unit for module clu and controller CLUSession.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests clu functionality.
   */
  public function testCLUSession() {
    // Check that the basic functions of module clu.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
