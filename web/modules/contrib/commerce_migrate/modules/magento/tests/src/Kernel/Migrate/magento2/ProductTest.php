<?php

namespace Drupal\Tests\commerce_migrate_magento\Kernel\Migrate\magento2;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests Product migration.
 *
 * @requires migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_magento_m2
 */
class ProductTest extends CsvTestBase {

  use CommerceMigrateTestTrait;

  /**
   * File path of the test fixture.
   *
   * @var string
   */
  protected $fixtures = __DIR__ . '/../../../../fixtures/csv/magento2-catalog_product_20180326_013553_test.csv';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'action',
    'address',
    'entity',
    'field',
    'inline_entity_form',
    'options',
    'path',
    'system',
    'text',
    'user',
    'views',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce',
    'commerce_product',
    'commerce_migrate',
    'commerce_migrate_magento',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    // Make sure uid 1 is created.
    user_install();

    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig('commerce_product');
    $this->createDefaultStore();
    $this->executeMigrations([
      'magento2_product_variation_type',
      'magento2_product_variation',
      'magento2_product_type',
      'magento2_product',
    ]);
  }

  /**
   * Test product migration.
   */
  public function testProduct() {
    $this->assertProductEntity(1, 'bag', '1', 'Joust Duffle Bag', TRUE, ['1'], ['1']);
    $this->assertProductVariationEntity(1, 'bag', '1', '24-MB01', '34.000000', 'USD', '1', 'Joust Duffle Bag', 'default', '1521962400', NULL);

    $this->assertProductEntity(7, 'gear', '1', 'Sprite Foam Roller', TRUE, ['1'], ['7']);
    $this->assertProductVariationEntity(7, 'gear', '1', '24-WG088', '19.000000', 'USD', '7', 'Sprite Foam Roller', 'default', '1521962400', NULL);

    $this->assertProductEntity(8, 'sprite_stasis_ball', '1', 'Sprite Stasis Ball 55 cm', TRUE, ['1'], ['8']);
    $this->assertProductVariationEntity(8, 'sprite_stasis_ball', '1', '24-WG081-gray', '23.000000', 'USD', '8', 'Sprite Stasis Ball 55 cm', 'default', '1521962400', NULL);

  }

}
