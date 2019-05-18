<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Functional\uc7;

use Drupal\Tests\commerce_migrate_ubercart\Functional\MigrateUpgradeReviewPageTestBase;

/**
 * Tests migrate upgrade review page for Ubercart 7.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class MigrateUpgradeReviewPageTest extends MigrateUpgradeReviewPageTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'action',
    'address',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_order',
    'commerce_product',
    'commerce_migrate',
    'commerce_migrate_ubercart',
    'commerce_shipping',
    'entity',
    'entity_reference_revisions',
    'inline_entity_form',
    'path',
    'profile',
    'physical',
    'state_machine',
    'text',
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
  protected function getAvailablePaths() {
    $paths = [
      'action',
      'addressfield',
      'block',
      'blog',
      'bulk_export',
      'color',
      'comment',
      'contact',
      'contextual',
      'ctools',
      'ctools_access_ruleset',
      'ctools_ajax_sample',
      'ctools_custom_content',
      'dashboard',
      'date',
      'dblog',
      'email',
      'entity',
      'entity_feature',
      'entity_token',
      'entityreference',
      'field',
      'field_sql_storage',
      'field_ui',
      'file',
      'filter',
      'help',
      'image',
      'link',
      'list',
      'menu',
      'node',
      'number',
      'openid',
      'options',
      'overlay',
      'page_manager',
      'path',
      'phone',
      'php',
      'poll',
      'rdf',
      'search',
      'search_embedded_form',
      'search_extra_type',
      'search_node_tags',
      'shortcut',
      'simpletest',
      'stylizer',
      'system',
      'taxonomy',
      'term_depth',
      'text',
      'toolbar',
      'translation',
      'trigger',
      'uc_attribute',
      'uc_flatrate',
      'uc_order',
      'uc_product',
      'uc_store',
      'user',
      'views_content',
      'views_ui',
    ];
    return $paths;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    $paths = [
      'aggregator',
      'book',
      'forum',
      'locale',
      'profile',
      'rules',
      'rules_admin',
      'rules_i18n',
      'rules_scheduler',
      'statistics',
      'syslog',
      'test_gateway',
      'token',
      'tracker',
      'uc_2checkout',
      'uc_ajax_admin',
      'uc_authorizenet',
      'uc_cart',
      'uc_cart_links',
      'uc_catalog',
      'uc_credit',
      'uc_cybersource',
      'uc_file',
      'uc_google_checkout',
      'uc_googleanalytics',
      'uc_payment',
      'uc_payment_pack',
      'uc_paypal',
      'uc_product_kit',
      'uc_quote',
      'uc_reports',
      'uc_roles',
      'uc_shipping',
      'uc_stock',
      'uc_tax_report',
      'uc_taxes',
      'uc_ups',
      'uc_usps',
      'uc_weightquote',
      'update',
      'views',
    ];
    return $paths;
  }

}
