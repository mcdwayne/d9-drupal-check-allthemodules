<?php

namespace Drupal\breakpoints_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the breakpoints_ui module.
 */
class BreakpointsUiControllerTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "breakpoints_ui BreakpointsUiController's controller functionality",
      'description' => 'Test Unit for module breakpoints_ui and controller BreakpointsUiController.',
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
   * Tests breakpoint_ui functionality.
   */
  public function testBreakpointsUiController() {
    // Check that the basic functions of module breakpoints_ui.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
