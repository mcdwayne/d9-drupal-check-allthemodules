<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests product type migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProductTypeTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'path',
    'commerce_price',
    'commerce_product',
    'commerce_store',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    // @todo Execute the d7_field and d7_field_instance migrations?
    $migration = $this->getMigration('commerce1_product_type');
    $this->executeMigration($migration);

    // Rerun the migration.
    $table_name = $migration->getIdMap()->mapTableName();
    $default_connection = \Drupal::database();
    $default_connection->truncate($table_name)->execute();
    $this->executeMigration($migration);
  }

  /**
   * Test product type migration from Drupal 7 to 8.
   */
  public function testProductType() {
    $type = [
      'id' => 'bags_cases',
      'label' => 'Bags & Cases',
      'description' => 'A <em>Bags & Cases</em> is a content type which contain product variations.',
      'variation_type' => 'bags_cases',
    ];
    $this->assertProductTypeEntity($type['id'], $type['label'], $type['description'], $type['variation_type']);
    $type = [
      'id' => 'tops',
      'label' => 'Tops',
      'description' => 'A <em>Tops</em> is a content type which contain product variations.',
      'variation_type' => 'tops',
    ];
    $this->assertProductTypeEntity($type['id'], $type['label'], $type['description'], $type['variation_type']);
  }

}
