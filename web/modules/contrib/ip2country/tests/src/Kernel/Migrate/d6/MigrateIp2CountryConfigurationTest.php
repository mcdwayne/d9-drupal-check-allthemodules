<?php

namespace Drupal\Tests\ip2country\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Migrates various configuration objects owned by the Ip2Country module.
 *
 * @group migrate_ip2country_6
 */
class MigrateIp2CountryConfigurationTest extends MigrateDrupal6TestBase {

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
   * Expected contents of our configuration(s) after migration.
   *
   * The fixture deliberately contains non-default values for all these
   * variables in order to ensure they overwrite the default D8 values.
   *
   * @var array
   */
  protected $expectedConfig = [
    // Same order as in config schema.
    'ip2country.settings' => [
      'watchdog' => FALSE,
      'rir' => 'lacnic',
      'update_interval' => 302400,
      'debug' => TRUE,
      'test_type' => 1,
      'test_country' => 'UZ',
      'test_ip_address' => '73.140.122.15',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $migrations = ['d6_ip2country_settings'];
    $this->executeMigrations($migrations);
  }

  /**
   * Tests that all configurations values got migrated as expected.
   */
  public function testConfigurationMigration() {
    foreach ($this->expectedConfig as $config_id => $values) {
      $actual = \Drupal::config($config_id)->get();
      unset($actual['_core']);
      // Comparison via assertSame() requires arrays to be in identical order.
      ksort($actual);
      ksort($values);
      $this->assertSame($actual, $values, $config_id . ' matches expected values.');
    }
  }

}
