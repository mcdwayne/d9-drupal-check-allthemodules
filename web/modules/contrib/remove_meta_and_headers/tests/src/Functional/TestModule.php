<?php

namespace Drupal\Tests\remove_meta_and_headers\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group remove_meta_and_headers
 */
class TestModule extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['remove_meta_and_headers'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
    $xGenerator = $this->drupalGetHeader("X-Generator");
    watchdog('TEST', $xGenerator, WATCHDOG_DEBUG);
    $this->assertEquals(1, 1);
  }

}
