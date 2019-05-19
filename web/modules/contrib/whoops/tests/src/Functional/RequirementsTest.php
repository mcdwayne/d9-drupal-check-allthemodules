<?php

namespace Drupal\Tests\whoops\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests whoops requirements.
 *
 * @group whoops
 */
class RequirementsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['whoops'];

  /**
   * Tests that the status page shows a warning when whoops is enabled.
   */
  public function testStatusPage() {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains('Whoops module enabled');
    $this->assertSession()->pageTextContains('The module registers an error handler which provide debug information, therefore it is not suitable for a production environment.');
  }

}
