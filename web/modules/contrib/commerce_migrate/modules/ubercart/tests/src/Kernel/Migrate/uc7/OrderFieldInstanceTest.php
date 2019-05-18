<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\field\Entity\FieldConfig;

/**
 * Migrate field instance tests.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class OrderFieldInstanceTest extends Ubercart7TestBase {

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
    $this->installEntitySchema('profile');
    $this->installConfig('commerce_order');
    $this->executeMigrations([
      'uc_order_field',
      'uc_order_field_instance',
    ]);
  }

  /**
   * Tests migration of Ubercart 7 field instances.
   */
  public function testFieldInstanceMigration() {
    $fields = [
      'commerce_order.default.field_order_comments',
      'commerce_order.default.field_order_admin_comments',
      'commerce_order.default.field_order_logs',
    ];
    foreach ($fields as $field) {
      /** @var \Drupal\field\Entity\FieldStorageConfig $storage */
      $field_config = FieldConfig::load($field);
      $this->assertInstanceOf(FieldConfig::class, $field_config, "$field is not an instance of FieldConfig");
    }
  }

}
