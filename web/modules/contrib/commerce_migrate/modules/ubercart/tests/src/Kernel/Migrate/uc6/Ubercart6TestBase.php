<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\migrate\MigrateExecutable;

/**
 * Test base for Ubercart D6 tests.
 */
abstract class Ubercart6TestBase extends MigrateDrupal6TestBase {

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
    return __DIR__ . '/../../../../fixtures/uc6.php';
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
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product']);
    $this->executeMigrations([
      'uc_attribute_field',
      'uc_product_attribute',
      'uc_attribute_field_instance',
      'uc_attribute_instance_widget_settings',
    ]);
  }

  /**
   * Migrate node and product types.
   *
   * Required modules:
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - migrate_plus.
   * - node.
   * - path.
   */
  protected function migrateContentTypes() {
    parent::migrateContentTypes();
    $this->installEntitySchema('commerce_product');
    $this->executeMigration('uc6_product_type');
  }

  /**
   * Executes all field migrations.
   */
  protected function migrateFields() {
    $this->migrateContentTypes();
    $this->executeMigrations([
      'd6_field',
      'd6_field_instance',
      'd6_field_instance_widget_settings',
      'd6_view_modes',
      'd6_field_formatter_settings',
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
   * - content_translation.
   * - language.
   * - migrate_plus.
   * - path.
   * - profile.
   * - state_machine.
   */
  protected function migrateOrders() {
    $this->migrateOrderItems();
    $this->executeMigrations([
      'uc_order_field',
      'uc_order_field_instance',
      'uc6_order',
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
   * - content_translation.
   * - language.
   * - migrate_plus.
   * - path.
   * - profile.
   * - state_machine.
   */
  protected function migrateOrderItems() {
    $this->installEntitySchema('view');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('node');
    $this->installConfig(['commerce_order', 'commerce_product']);
    $this->migrateStore();
    $this->migrateContentTypes();
    $this->migrateAttributes();
    $this->executeMigrations([
      'd6_language_content_settings',
      'uc6_language_content_settings',
      'uc6_product_variation',
      'd6_node',
      'uc6_profile_billing',
      'uc6_order_product',
    ]);
  }

  /**
   * Executes product variation migration.
   *
   * Required modules:
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - content_translation.
   * - language.
   * - menu_ui.
   * - migrate_plus.
   * - path.
   */
  protected function migrateProducts() {
    $this->migrateProductVariations();
  }

  /**
   * Executes product variation migration.
   *
   * Required modules:
   * - commerce_price.
   * - commerce_product.
   * - commerce_store.
   * - filter.
   * - menu_ui.
   * - migrate_plus.
   * - node.
   * - path.
   */
  protected function migrateProductVariations() {
    $this->installEntitySchema('node');
    $this->installEntitySchema('view');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(static::$modules);
    $this->migrateStore();
    $this->migrateUsers(FALSE);
    $this->migrateFields();
    $this->migrateAttributes();
    $this->executeMigrations([
      'language',
      'd6_language_content_settings',
      'uc6_language_content_settings',
      'uc6_product_variation_type',
      'uc6_product_variation',
      'd6_node',
      'd6_node_translation',
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
      'uc6_store',
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
