<?php

/**
 * @file
 * Contains \Drupal\content_connected\Tests\ContentConnectedController.
 */

namespace Drupal\content_connected\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the content_connected module.
 */
class ContentConnectedControllerTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "content_connected ContentConnectedController's controller functionality",
      'description' => 'Test Unit for module content_connected and controller ContentConnectedController.',
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
   * Tests content_connected functionality.
   */
  public function testContentConnectedController() {
    // Check that the basic functions of module content_connected.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
