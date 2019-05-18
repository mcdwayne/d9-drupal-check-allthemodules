<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Functional\uc7;

use Drupal\Tests\commerce_migrate_ubercart\Functional\MigrateUpgradeExecuteTestBase;

/**
 * Tests Ubercart 7 migration using the Migrate Drupal UI.
 *
 * @requires module migrate_plus
 * @requires module commerce_shipping
 * @requires module physical
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class MigrateUpgradeUbercart7Test extends MigrateUpgradeExecuteTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'address',
    'block',
    'block_content',
    'comment',
    'commerce',
    'commerce_cart',
    'commerce_log',
    'commerce_migrate',
    'commerce_migrate_ubercart',
    'commerce_order',
    'commerce_payment',
    'commerce_price',
    'commerce_product',
    'commerce_promotion',
    'commerce_shipping',
    'commerce_store',
    'commerce_tax',
    'datetime',
    'dblog',
    'entity',
    'entity_reference_revisions',
    'field',
    'file',
    'filter',
    'image',
    'inline_entity_form',
    'link',
    'migrate',
    'migrate_drupal',
    'migrate_drupal_ui',
    'migrate_plus',
    'node',
    'options',
    'path',
    'physical',
    'profile',
    'search',
    'shortcut',
    'state_machine',
    'system',
    'taxonomy',
    'telephone',
    'text',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture(drupal_get_path('module', 'commerce_migrate_ubercart') . '/tests/fixtures/uc7.php');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    return [
      'action' => 25,
      'base_field_override' => 3,
      'block' => 28,
      'block_content' => 1,
      'block_content_type' => 1,
      'comment' => 1,
      'comment_type' => 5,
      'commerce_currency' => 1,
      'commerce_log' => 0,
      'commerce_order' => 5,
      'commerce_order_item' => 7,
      'commerce_order_item_type' => 1,
      'commerce_order_type' => 1,
      'commerce_package_type' => 0,
      'commerce_payment' => 4,
      'commerce_payment_gateway' => 1,
      'commerce_payment_method' => 0,
      'commerce_product' => 3,
      'commerce_product_attribute' => 4,
      'commerce_product_attribute_value' => 6,
      'commerce_product_type' => 3,
      'commerce_product_variation' => 3,
      'commerce_product_variation_type' => 3,
      'commerce_promotion' => 0,
      'commerce_promotion_coupon' => 0,
      'commerce_shipment' => 0,
      'commerce_shipment_type' => 1,
      'commerce_shipping_method' => 1,
      'commerce_store' => 1,
      'commerce_store_type' => 1,
      'commerce_tax_type' => 3,
      'contact_form' => 2,
      'contact_message' => 0,
      'date_format' => 11,
      'editor' => 2,
      'entity_form_display' => 19,
      'entity_form_mode' => 2,
      'entity_view_display' => 26,
      'entity_view_mode' => 16,
      'field_config' => 42,
      'field_storage_config' => 31,
      'file' => 1,
      'filter_format' => 5,
      'image_style' => 3,
      'menu' => 5,
      'menu_link_content' => 2,
      'migration' => 0,
      'migration_group' => 1,
      'node' => 2,
      'node_type' => 2,
      'profile' => 3,
      'profile_type' => 1,
      'rdf_mapping' => 5,
      'search_page' => 2,
      'shortcut' => 4,
      'shortcut_set' => 1,
      'taxonomy_term' => 1,
      'taxonomy_vocabulary' => 2,
      'tour' => 1,
      'user' => 4,
      'user_role' => 3,
      'view' => 25,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {}

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [];
  }

}
