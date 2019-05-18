<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests tax type migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class TaxTypeTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_tax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_tax_type');
    $this->executeMigration('uc7_tax_type');
  }

  /**
   * Test tax migration.
   */
  public function testTaxType() {
    $territory = [['country_code' => 'CA']];
    $this->assertTaxType('station_maintenance', 'Station maintenance', 'custom', '0.05', $territory);
    $this->assertTaxType('ca', 'CA', 'custom', '0.2', $territory);
    $this->assertTaxType('us', 'US', 'custom', '0.4', $territory);
  }

}
