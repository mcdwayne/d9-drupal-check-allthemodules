<?php

/**
 * @file
 * Contains \Drupal\link_click_count\Tests\LinkClickCountController.
 */

namespace Drupal\link_click_count\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the link_click_count module.
 */
class LinkClickCountControllerTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "link_click_count LinkClickCountController's controller functionality",
      'description' => 'Test Unit for module link_click_count and controller LinkClickCountController.',
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
   * Tests link_click_count functionality.
   */
  public function testLinkClickCountController() {
    // Check that the basic functions of module link_click_count.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
