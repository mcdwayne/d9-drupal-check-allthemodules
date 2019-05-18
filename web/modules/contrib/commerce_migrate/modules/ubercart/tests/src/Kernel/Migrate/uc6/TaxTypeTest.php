<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests tax type migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class TaxTypeTest extends Ubercart6TestBase {

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
    $this->executeMigration('uc6_tax_type');
  }

  /**
   * Test tax migration.
   */
  public function testTaxType() {
    $this->assertTaxType('handling', 'Handling', 'custom', '0.04', [['country_code' => 'US']]);
  }

}
