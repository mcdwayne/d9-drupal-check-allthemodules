<?php

namespace Drupal\Tests\ip2country\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of Ip2Country {user}.data into {user_data}.
 *
 * @group migrate_ip2country_6
 */
class MigrateIp2CountryUserDataTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ip2country'];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/migrate/drupal6.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Need to migrate users, but don't need pictures.
    $this->migrateUsers(FALSE);
    $this->installSchema('user', ['users_data']);
    $this->executeMigration('d6_ip2country_user_data');
  }

  /**
   * Tests the Drupal6 Ip2Country user settings migration.
   */
  public function testIp2CountryUserData() {
    // In D6 and D7, {users}.data stores a serialized array containing
    // at most one key => value pair of ip2country data in the form
    // 's:18:"country_iso_code_2";s:2:"US";'.
    //
    // In D8, the user.data service stores data as rows in {users_data}.
    // Each row contains the values 'uid', 'module', 'name', 'value',
    // and 'serialized'.
    //
    // $userData->get($module, $uid, $key); returns a string (the 'value')
    // for the row identified by the $module, $uid, and the $key.
    // The ip2country module uses the country_iso_code_2 'key' to store a
    // 2-character country code 'value'. Unserialization is done automatically
    // if necessary.
    $userData = \Drupal::service('user.data');
    $module = 'ip2country';
    $key = 'country_iso_code_2';

    $uid = 2;
    $setting = $userData->get($module, $uid, $key);
    $this->assertSame('CA', $setting);

    $uid = 8;
    $setting = $userData->get($module, $uid, $key);
    $this->assertSame(NULL, $setting);

    $uid = 15;
    $setting = $userData->get($module, $uid, $key);
    $this->assertSame('ES', $setting);
  }

}
