<?php

namespace Drupal\be_sure\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the be_sure module.
 */
class BeSureControllerTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "be_sure BeSureController's controller functionality",
      'description' => 'Test Unit for module be_sure and controller BeSureController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests be_sure functionality.
   */
  public function testBeSureController() {
    // Check that the basic functions of module be_sure.
    $this->assertEqual(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
