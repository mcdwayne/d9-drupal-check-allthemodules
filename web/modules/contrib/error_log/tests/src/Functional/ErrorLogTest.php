<?php

namespace Drupal\Tests\error_log\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Error Log module.
 *
 * @group error_log
 */
class ErrorLogTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['error_log'];

  /**
   * Tests Error Log module.
   */
  public function testErrorLog() {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
    $this->drupalPostForm('admin/config/development/logging', [], t('Save configuration'));
    $log = file(DRUPAL_ROOT . '/' . $this->siteDirectory . '/error.log');
    $this->assertIdentical(count($log), 1);
    $this->assertIdentical(preg_match('/\[.*\] \[notice\] \[user\] .* Session opened for /', $log[0]), 1);
  }

}
