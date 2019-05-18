<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests migration of Ubercart 7 fields.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class OrderFieldTest extends Ubercart7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'migrate_plus',
    'node',
    'path',
    'profile',
    'state_machine',
    'telephone',
    'text',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_order');
    $this->executeMigration('uc_order_field');
  }

  /**
   * Tests the Ubercart 7 field to Drupal 8 migration.
   */
  public function testFields() {
    $field_storages = [
      'commerce_order.field_order_comments',
      'commerce_order.field_order_admin_comments',
      'commerce_order.field_order_logs',
    ];
    foreach ($field_storages as $field_storage) {
      /** @var \Drupal\field\Entity\FieldStorageConfig $storage */
      $storage = FieldStorageConfig::load($field_storage);
      $this->assertInstanceOf(FieldStorageConfig::class, $storage, "$field_storage is not an instance of FieldStorageConfig");
    }
  }

}
