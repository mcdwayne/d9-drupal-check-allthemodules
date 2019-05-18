<?php

namespace Drupal\Tests\ip_anon\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests basic IP Anonymize functionality.
 *
 * @group IP Anonymize
 */
class IpAnonymizeTestCase extends BrowserTestBase {

  use CronRunTrait;
  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['ip_anon', 'dblog'];

  /**
   * Basic tests for IP Anonymize module.
   */
  public function testIpAnonymize() {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);

    $this->assertTrue($this->getIp());

    $config['policy'] = 1;
    $config['period_watchdog'] = 0;
    $this->drupalPostForm('admin/config/people/ip_anon', $config, $this->t('Save configuration'));

    $this->cronRun();

    $this->assertFalse($this->getIp());
  }

  /**
   * Get IP address from watchdog table.
   */
  protected function getIp() {
    return \Drupal::database()->select('watchdog', 'w')
      ->fields('w', ['hostname'])
      ->orderBy('wid')
      ->range(0, 1)
      ->execute()
      ->fetchField();
  }

}
