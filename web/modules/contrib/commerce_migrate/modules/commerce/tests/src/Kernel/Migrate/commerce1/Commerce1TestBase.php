<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Tests\commerce_cart\Traits\CartManagerTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\migrate\MigrateExecutable;

/**
 * Base class for Commerce 1 migration tests.
 */
abstract class Commerce1TestBase extends MigrateDrupal7TestBase {

  use CartManagerTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'action',
    'address',
    'commerce',
    'entity',
    'entity_reference_revisions',
    'inline_entity_form',
    'profile',
    'state_machine',
    'text',
    'views',
    // Commerce migrate requirements.
    'commerce_migrate',
    'commerce_migrate_commerce',
  ];

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/ck2.php';
  }

  /**
   * Executes field migration.
   *
   * Required modules:
   * - comment.
   * - datetime.
   * - field.
   * - image.
   * - link.
   * - menu_ui.
   * - node.
   * - profile.
   * - taxonomy.
   * - telephone.
   * - text.
   */
  protected function migrateFields() {
    $this->migrateContentTypes();
    $this->migrateCommentTypes();
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('profile');
    $this->installConfig(['commerce_product']);
    $this->executeMigration('d7_field');
    $this->executeMigrations(['d7_taxonomy_vocabulary', 'd7_field_instance']);
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
      'commerce1_product_variation_type',
      'commerce1_product_type',
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
   */
  protected function migrateOrders() {
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('profile');
    $this->installConfig(['commerce_order']);
    $this->installConfig(['commerce_product']);
    $this->migrateStore();
    // @todo Execute the d7_field and d7_field_instance migrations?
    $this->migrateProfiles();
    $this->executeMigrations([
      'commerce1_product_variation_type',
      'commerce1_product_variation',
      'commerce1_order_item_type',
      'commerce1_order_item',
      'commerce1_order',
    ]);
  }

  /**
   * Executes order migration with the cart enabled.
   *
   * Required modules:
   * - commerce_order.
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - migrate_plus.
   * - path.
   */
  protected function migrateOrdersWithCart() {
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('profile');
    $this->installConfig(['commerce_order']);
    $this->installConfig(['commerce_product']);
    $this->installCommerceCart();
    $this->migrateStore();
    // @todo Execute the d7_field and d7_field_instance migrations?
    $this->migrateProfiles();
    $this->executeMigrations([
      'commerce1_product_variation_type',
      'commerce1_product_variation',
      'commerce1_order_item_type',
      'commerce1_order_item',
      'commerce1_order',
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
   */
  protected function migrateOrderItems() {
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('profile');
    $this->installConfig(['commerce_order']);
    $this->migrateProducts();
    $this->executeMigrations([
      'commerce1_order_item_type',
      'commerce1_order_item',
    ]);
  }

  /**
   * Executes product migration.
   *
   * Required modules:
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - path.
   */
  protected function migrateProducts() {
    $this->installEntitySchema('commerce_product');
    $this->migrateStore();
    $this->migrateProductVariations();
    $this->executeMigrations([
      'commerce1_product_type',
      'commerce1_product',
    ]);
  }

  /**
   * Executes product variation migration.
   *
   * Required modules:
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - path.
   */
  protected function migrateProductVariations() {
    $this->installEntitySchema('view');
    $this->installEntitySchema('commerce_product_variation');
    $this->executeMigrations([
      'commerce1_product_variation_type',
      'commerce1_product_variation',
    ]);
  }

  /**
   * Executes profile migrations.
   *
   * Required modules:
   * - commerce_order.
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - migrate_plu.
   * - path.
   */
  protected function migrateProfiles() {
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('view');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_product');
    $this->installConfig('profile');
    $this->migrateUsers(FALSE);
    $this->executeMigrations([
      'commerce1_profile_type',
      'commerce1_profile',
      'commerce1_profile_revision',
    ]);
  }

  /**
   * Executes store migration.
   *
   * Required modules:
   * - commerce_currency.
   * - commerce_store.
   */
  protected function migrateStore() {
    $this->installEntitySchema('commerce_store');
    $this->migrateUsers(FALSE);
    $this->executeMigrations([
      'commerce1_currency',
      'commerce1_store',
      'commerce1_default_store',
    ]);
  }

  /**
   * Executes rollback on a single migration.
   *
   * @param string|\Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to rollback, or its ID.
   */
  protected function executeRollback($migration) {
    if (is_string($migration)) {
      $this->migration = $this->getMigration($migration);
    }
    else {
      $this->migration = $migration;
    }
    (new MigrateExecutable($this->migration, $this))->rollback();
  }

}
