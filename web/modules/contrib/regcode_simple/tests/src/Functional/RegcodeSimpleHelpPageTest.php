<?php

namespace Drupal\Tests\regcode_simple\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Make sure help page text populates and is readable only with proper perms.
 *
 * @group regcode_simple
 */
class RegcodeSimpleHelpPageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['regcode_simple', 'help'];

  /**
   * Make sure admin can read help text.
   */
  public function testAdminUserCanReadHelp() {

    // Create a user and login.
    $account = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($account);

    // Verify module is listed in help pages.
    $this->drupalGet('admin/help');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Registration Code (Simple)');
    $this->assertLink('Registration Code (Simple)', 0, 'Check Help page has module name as link.', 'Regcode Simple');
    // Verify module age has(at least some) correct content.
    $this->clickLink('Registration Code (Simple)');
    $this->assertSession()->pageTextContains('Registration Code (Simple)');
    $this->assertSession()->pageTextContains('For the password validation (and complexity) logic these options are available:');
  }

}
