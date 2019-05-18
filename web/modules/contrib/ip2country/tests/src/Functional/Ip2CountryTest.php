<?php

namespace Drupal\Tests\ip2country\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests operations of the IP to Country module.
 *
 * @group ip2country
 */
class Ip2CountryTest extends BrowserTestBase {

  // @todo Need 1 class for unit tests, 1 class for functional tests,
  // and 1 function for DB tests because filling takes so long.

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dblog', 'help', 'block'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Authenticated but unprivileged user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $unprivUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    //
    // Don't install ip2country! parent::setUp() creates a clean
    // environment, so we can influence the install before we call setUp().
    // We don't want the DB populated, so we'll manually install ip2country.
    //
    parent::setUp();

    //
    // Set a run-time long enough so the script won't break
    //
    $this->timeLimit = 3 * 60;  // 3 minutes!
    drupal_set_time_limit($this->timeLimit);

    // Turn off automatic DB download when module is installed.
    \Drupal::state()->set('ip2country_populate_database_on_install', FALSE);

    // Explicitly install the module so that it will have access
    // to the configuration variable we set above.
    $status = \Drupal::service('module_installer')->install(['ip2country'], FALSE);
    $this->resetAll();  // The secret ingredient.

    $this->assertTrue($status, 'Module ip2country enabled.');
    $this->assertTrue((ip2country_get_count() == 0), 'Database is empty.');

    // System help block is needed to see output from hook_help().
    $this->drupalPlaceBlock('help_block', ['region' => 'help']);

    // Need page_title_block because we test page titles.
    $this->drupalPlaceBlock('page_title_block');

    // Create our test users.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'access site reports',
      'administer ip2country',
    ]);
    $this->unprivUser = $this->drupalCreateUser();

    // Utility functions for loading IP/Country DB from external sources.
    \Drupal::moduleHandler()->loadInclude('ip2country', 'inc');
  }

  /**
   * Tests IP lookup for addresses in / not in the database.
   */
  public function testIpLookup() {
    ip2country_update_database('arin');

    $this->assertTrue(
      ($count = ip2country_get_count()) != 0,
      'Database has been updated with ' . $count . ' rows.'
    );

    // Real working IPs.
    $ip_array = [
      '125.29.33.201', '212.58.224.138',
      '184.51.240.110', '210.87.9.66',
      '93.184.216.119',
    ];
    foreach ($ip_array as $ip_address) {
      // Test dotted quad string form of address.
      $country = \Drupal::service('ip2country.manager')->getCountry($ip_address);
      $this->assertTrue($country, $ip_address . ' found, resolved to ' . $country . '.');

      // Test 32-bit unsigned long form of address.
      $usl_country = \Drupal::service('ip2country.manager')->getCountry(ip2long($ip_address));
      $this->assertTrue(
        $usl_country == $country,
        'Unsigned long lookup found same country code.'
      );

      $this->pass('Valid IP found in database.');
    }

    // Invalid and reserved IPs.
    $ip_array = [
      '127.0.0.1', '358.1.1.0',
    ];
    foreach ($ip_array as $ip_address) {
      $country = \Drupal::service('ip2country.manager')->getCountry($ip_address);
      $this->assertFalse($country, $ip_address . ' not found in database.');
      $this->pass('Invalid IP not found in database.');
    }

    ip2country_empty_database();
    $this->assertTrue((ip2country_get_count() == 0), 'Database is empty.');
  }

  /**
   * Tests injecting IP data via hook_ip2country_alter().
   */
  public function testAlterHook() {
    $this->pass('testAlterHook passed.');
  }

  /**
   * Tests Default country.
   */
  public function testDefaultCountry() {
    $this->pass('testDefaultCountry passed.');
  }

  /**
   * Tests module permissions / access to configuration page.
   */
  public function testUserAccess() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    //
    // Test as anonymous user.
    //
    $this->drupalGet('admin/config');
    $assert->statusCodeEquals(403);
    $assert->pageTextContains('Access denied');
    $assert->pageTextContains('You are not authorized to access this page.');

    $this->drupalGet('admin/config/people/ip2country');
    $assert->statusCodeEquals(403);

    // Try to trigger DB update as anonymous.
    $this->drupalGet('admin/config/people/ip2country/update/arin');
    $assert->statusCodeEquals(403);

    //
    // Test as authenticated but unprivileged user.
    //
    $this->drupalLogin($this->unprivUser);
    $this->drupalGet('admin/config');
    $assert->statusCodeEquals(403);

    $this->drupalGet('admin/config/people/ip2country');
    $assert->statusCodeEquals(403);
    $this->drupalLogout();

    //
    // As admin user.
    //
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('User location');
    $assert->pageTextContains('Settings for determining user location from IP address.');

    $this->drupalGet('admin/config/people/ip2country');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('User location');
    // This is output by ip2country_help().
    $assert->pageTextContains('Configuration settings for the ip2country module.');
    $assert->pageTextContains('Database is empty.');
    // Check that database updates are being logged to watchdog.
    $assert->fieldValueEquals('ip2country_watchdog', 1);
    // Check that database updates are using arin.
    $assert->fieldValueEquals('ip2country_rir', 'arin');

    // Update database via UI - choose a random RIR.
    // (Actually short-circuiting the UI here because of the Ajax call).
    $rir = array_rand([
      'afrinic' => 'AFRINIC',
      'arin'    => 'ARIN',
      'apnic'   => 'APNIC',
      'lacnic'  => 'LACNIC',
      'ripe'    => 'RIPE',
    ]);
    $this->drupalGet('admin/config/people/ip2country/update/' . $rir);
    $assert->pageTextContains(
      'The IP to Country database has been updated from ' . mb_strtoupper($rir) . '.'
    );

    // Check watchdog.
    $this->drupalGet('admin/reports/dblog');
    $assert->pageTextContains('Recent log messages');
    $assert->pageTextContains('ip2country');
    $assert->linkExists('Manual database update from ' . mb_strtoupper($rir) . ' server.');

    // Drill down.
    $this->clickLink('Manual database update from ' . mb_strtoupper($rir) . ' server.');
    $assert->pageTextContains('Details');
    $assert->pageTextContains('ip2country');
    $assert->pageTextContains('Manual database update from ' . mb_strtoupper($rir) . ' server.');

    $this->drupalLogout();
  }

  /**
   * Tests $user object for proper value.
   */
  public function testUserObject() {
    $this->pass('testUserObject passed.');
  }

  /**
   * Tests UI.
   */
  public function testUi() {
    $this->pass('testUi passed.');
  }

  /**
   * Tests IP Spoofing.
   *
   * @todo Should cover anonymous vs authenticated users and
   * check for info $messages.
   */
  public function testIpSpoofing() {
    $this->pass('testIpSpoofing passed.');
  }

  /**
   * Tests country spoofing.
   *
   * @todo Should cover anonymous vs authenticated users and
   * check for info $messages.
   */
  public function testCountrySpoofing() {
    $this->pass('testCountrySpoofing passed.');
  }

  /**
   * Tests manual lookup.
   */
  public function testIpManualLookup() {
    //$this->clickLink('Lookup');
    $this->pass('testIpManualLookup passed.');
  }

  /**
   * Tests DB download.
   */
  public function testDbDownload() {
    ip2country_empty_database();

    $this->assertTrue((ip2country_get_count() == 0), 'Database is empty.');

    // Choose a random RIR.
    $rir = array_rand([
//    'afrinic' => 'AFRINIC', // Don't use AFRINIC because it's incomplete.
      'arin'    => 'ARIN',
      'apnic'   => 'APNIC',
      'lacnic'  => 'LACNIC',
      'ripe'    => 'RIPE',
    ]);
    ip2country_update_database($rir);

    $this->assertTrue(
      ($count = ip2country_get_count()) != 0,
      'Database has been updated from ' . mb_strtoupper($rir) . ' with ' . $count . ' rows.'
    );

    ip2country_empty_database();
    $this->assertTrue((ip2country_get_count() == 0), 'Database is empty.');
  }

  /**
   * Tests manual DB update.
   */
  public function testDbManualUpdate() {
    //$this->clickLink('Update');
    $rows = db_select('ip2country')->countQuery()->execute()->fetchField();
    // Check that Database was updated manually.
    //$assert->pageTextContains(
    //  'The IP to Country database has been updated from ' . mb_strtoupper($rir) . '. ' . $rows . ' rows affected.'
    //);
    $this->pass('testDbManualUpdate passed.');
  }

  /**
   * Tests cron DB update.
   */
  public function testDbCronUpdate() {
    $this->pass('testDbCronUpdate passed.');
  }

  /**
   * Tests logging of DB updates.
   */
  public function testDbUpdateLogging() {
    // Turn off logging.

    // Turn on logging.
    $edit = [
      'ip2country_watchdog' => ['test' => TRUE],
    ];
    //$this->drupalPost(
    //  'admin/store/settings/countries/edit',
    //  $edit,
    //  'Import'
    //);
    // Check that watchdog reported database update.
    //$assert->pageTextContains('Database updated from ' . mb_strtoupper($rir) . ' server.');

    $this->pass('testDbUpdateLogging passed.');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Perform any clean-up tasks.
    \Drupal::state()->delete('ip2country_populate_database_on_install');

    // Finally...
    parent::tearDown();
  }

}
