<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Test base for Ubercart 7 tests.
 */
abstract class Ubercart7TestBase extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Commerce requirements.
    'address',
    'commerce',
    'entity',
    'entity_reference_revisions',
    'inline_entity_form',
    'views',
    // Commerce migrate requirements.
    'commerce_migrate',
    'commerce_migrate_ubercart',
  ];

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/uc7.php';
  }

  /**
   * Executes attributes migrations.
   *
   * Required modules:
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - path.
   */
  protected function migrateAttributes() {
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);
    $this->executeMigrations([
      'uc_attribute_field',
      'uc_product_attribute',
      'uc_attribute_field_instance',
      'uc_attribute_instance_widget_settings',
    ]);
  }

  /**
   * Executes all field migrations for comments.
   *
   * Required modules:
   * - comment.
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - node.
   * - path.
   * - text.
   */
  protected function migrateCommentFields() {
    $this->migrateCommentTypes();
    $this->executeMigrations([
      'd7_comment_field',
      'uc7_comment_field',
      'uc7_comment_field_instance',
    ]);
  }

  /**
   * Executes comment type migration.
   *
   * Required modules:
   * - comment.
   */
  protected function migrateCommentTypes() {
    parent::migrateCommentTypes();
    $this->executeMigration('uc7_comment_type');
  }

  /**
   * Executes field migration.
   *
   * Required modules:
   * - comment.
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - image.
   * - migrate_plus.
   * - node.
   * - path.
   * - taxonomy.
   * - text.
   */
  protected function migrateFields() {
    $this->migrateContentTypes();
    $this->migrateCommentTypes();
    $this->executeMigrations([
      'uc7_product_type',
      'd7_taxonomy_vocabulary',
      'd7_field',
      'd7_field_instance',
    ]);
  }

  /**
   * Executes content type migration.
   *
   * Required modules:
   * - commerce_product.
   * - node.
   */
  protected function migrateContentTypes() {
    parent::migrateContentTypes();
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product']);
    $this->executeMigrations([
      'uc7_product_variation_type',
      'uc7_product_type',
    ]);
  }

  /**
   * Executes order item migration.
   *
   * Required modules:
   * - commerce_order.
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - migrate_plus.
   * - path.
   * - profile.
   * - state_machine.
   */
  protected function migrateOrderItems() {
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('profile');
    $this->migrateStore();
    $this->migrateContentTypes();
    $this->migrateAttributes();
    $this->executeMigrations([
      'uc7_product_variation',
      'd7_node',
      'uc7_profile_billing',
      'uc7_order_product',
    ]);
  }

  /**
   * Executes order migration.
   *
   * Required modules:
   * - commerce_order.
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - migrate_plus.
   * - path.
   * - profile.
   * - state_machine.
   */
  protected function migrateOrders() {
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('profile');
    $this->installConfig('commerce_order');
    $this->migrateOrderItems();
    $this->executeMigration('uc_order_field');
    $this->executeMigration('uc_order_field_instance');
    $this->executeMigration('uc7_order');
  }

  /**
   * Executes product migration.
   *
   * Required modules:
   * - commerce_order.
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - migrate_plus.
   * - path.
   * - profile.
   * - state_machine.
   */
  protected function migrateProducts() {
    $this->installEntitySchema('commerce_product');
    $this->installConfig(static::$modules);
    $this->migrateStore();
    $this->migrateContentTypes();
    $this->migrateFields();
    $this->migrateCommentTypes();
    $this->executeMigrations([
      'uc_attribute_field',
      'uc_product_attribute',
      'uc_attribute_field_instance',
      'uc7_product_variation',
      'd7_node',
    ]);
  }

  /**
   * Executes store migration.
   *
   * Required modules:
   * - commerce_price.
   * - commerce_store.
   */
  protected function migrateStore() {
    $this->installEntitySchema('commerce_store');
    $this->migrateUsers(FALSE);
    $this->executeMigrations([
      'uc_currency',
      'uc7_store',
    ]);
  }

}
