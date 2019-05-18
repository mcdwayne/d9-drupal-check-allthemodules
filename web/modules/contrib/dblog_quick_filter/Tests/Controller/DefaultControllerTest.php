<?php

namespace Drupal\dblog_quick_filter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the dblog_quick_filter module.
 */
class DefaultControllerTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "dblog_quick_filter DefaultController's controller functionality",
      'description' => 'Test Unit for module dblog_quick_filter and controller DefaultController.',
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
   * Tests dblog_quick_filter functionality.
   */
  public function testDefaultController() {
    // Check that the basic functions of module dblog_quick_filter.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
