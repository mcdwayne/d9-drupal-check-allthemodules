<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests tax type migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class TaxTypeTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_store',
    'commerce_tax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_tax_type');
    $this->executeMigration('commerce1_tax_type');
  }

  /**
   * Test tax migration.
   */
  public function testTaxType() {
    $territory = [['country_code' => 'NZ']];
    $this->assertTaxType('sample_michigan_sales_tax', 'sample_michigan_sales_tax', 'custom', '0.06', $territory);
  }

}
