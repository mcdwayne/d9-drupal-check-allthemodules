<?php

namespace Drupal\Tests\cryptolog\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Cryptolog module.
 *
 * @group cryptolog
 */
class CryptologTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dblog', 'cryptolog'];

  /**
   * Tests that cryptolog rewrites the client IP address.
   */
  public function testCryptolog() {
    // Create user.
    $admin_user = $this->drupalCreateUser(['access site reports']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/reports/dblog/event/1');
    $hostname_1 = $this->getLoggedHostname();
    $this->drupalGet('admin/reports/dblog/event/2');
    $hostname_2 = $this->getLoggedHostname();
    $this->assertNotEqual($hostname_1, $hostname_2);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/reports/dblog/event/3');
    $hostname_3 = $this->getLoggedHostname();
    $this->assertEqual($hostname_2, $hostname_3);
  }

  /**
   * Gets the logged hostname from the dblog details page.
   */
  public function getLoggedHostname() {
    $rows = $this->xpath('//table[@class="dblog-event"]/tbody/tr');
    foreach ($rows as $row) {
      if ($row->find('xpath', '/th')->getText() == 'Hostname') {
        return $row->find('xpath', '/td')->getText();
      }
    }
  }

}
