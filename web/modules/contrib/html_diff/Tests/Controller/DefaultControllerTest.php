<?php

/**
 * @file
 * Contains Drupal\html_diff\Tests\DefaultController.
 */

namespace Drupal\html_diff\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the html_diff module.
 */
class DefaultControllerTest extends WebTestBase
{

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "html_diff DefaultController's controller functionality",
      'description' => 'Test Unit for module html_diff and controller DefaultController.',
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
   * Tests html_diff functionality.
   */
  public function testDefaultController() {
    // Check that the basic functions of module html_diff.
    $this->assertEqual(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }
}
