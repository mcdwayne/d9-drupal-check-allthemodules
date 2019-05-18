<?php

namespace Drupal\Tests\ip2country\Kernel\Migrate\d7;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Upgrade ip2country date formats to core.date_format.*.yml.
 *
 * @group migrate_ip2country_7
 */
class MigrateIp2CountryDateFormatTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ip2country'];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/migrate/drupal7.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d7_ip2country_date_formats');
  }

  /**
   * Tests the Drupal 7 date formats to Drupal 8 migration.
   *
   * The fixture deliberately contains non-default values for the
   * ip2country_date and ip2country_time formats, in order to ensure that
   * the default D8 date format values are overwritten by the migration.
   */
  public function testDateFormats() {
    $dateOnly = DateFormat::load('ip2country_date');
    $this->assertSame('Y/j/n', $dateOnly->getPattern());

    $timeOnly = DateFormat::load('ip2country_time');
    $this->assertSame('s:i:H T', $timeOnly->getPattern());
  }

}
