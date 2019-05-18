<?php

namespace Drupal\Tests\packages\Functional;

use Drupal\Tests\packages\Functional\PackagesTestBase;

/**
 * Test the Packages components support including Views and Block.
 *
 * @group packages
 */
class PackagesComponentsTest extends PackagesTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'views',
    'packages',
    'packages_test',
  ];

  /**
   * Tests the views integration.
   */
  public function testViews() {
    // Log in.
    $this->drupalLogin($this->packagesUser);

    // Enable the test package.
    $this->submitPackagesForm(['test' => TRUE]);

    // The view should be accessible.
    $this->drupalGet('/package-test-view');
    $this->assertSession()->statusCodeEquals(200);

    // Disable the test package.
    $this->submitPackagesForm(['test' => FALSE]);

    // The view should be not accessible.
    $this->drupalGet('/package-test-view');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests the block integration.
   */
  public function testBlock() {
    // Log in.
    $this->drupalLogin($this->packagesUser);

    // Enable the test package.
    $this->submitPackagesForm(['test' => TRUE]);

    // Only one of the blocks should be present.
    $this->assertSession()->pageTextContains('Package test is active');
    $this->assertSession()->pageTextNotContains('Package test is not active');

    // Disable the test package.
    $this->submitPackagesForm(['test' => FALSE]);

    // Only one of the blocks should be present.
    $this->assertSession()->pageTextNotContains('Package test is active');
    $this->assertSession()->pageTextContains('Package test is not active');
  }

}
