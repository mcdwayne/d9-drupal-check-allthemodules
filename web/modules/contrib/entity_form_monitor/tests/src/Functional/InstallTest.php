<?php

namespace Drupal\Tests\entity_form_monitor\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module installed.
 *
 * @group entity_form_monitor
 */
class InstallTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['entity_form_monitor'];

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testInstall() {
    $this->drupalGet('<front>');
    $this->assertEquals(200, $this->getSession()->getStatusCode());
  }
}
