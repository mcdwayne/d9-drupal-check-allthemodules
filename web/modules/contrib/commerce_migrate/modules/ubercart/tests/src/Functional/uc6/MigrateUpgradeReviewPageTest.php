<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Functional\uc6;

use Drupal\Tests\commerce_migrate_ubercart\Functional\MigrateUpgradeReviewPageTestBase;

/**
 * Tests migrate upgrade review page for Ubercart 6.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
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
    $this->loadFixture(drupal_get_path('module', 'commerce_migrate_ubercart') . '/tests/fixtures/uc6.php');
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
      'block',
      'blog',
      'blogapi',
      'calendarsignup',
      'color',
      'comment',
      'contact',
      'content',
      'content_copy',
      'content_multigroup',
      'content_permissions',
      'date',
      'date_locale',
      'date_php4',
      'date_repeat',
      'date_timezone',
      'date_tools',
      'datepicker',
      'dblog',
      'email',
      'event',
      'fieldgroup',
      'filefield',
      'filefield_meta',
      'filter',
      'help',
      'i18nstrings',
      'i18nsync',
      'imageapi',
      'imageapi_gd',
      'imageapi_imagemagick',
      'imagecache',
      'imagecache_ui',
      'imagefield',
      'jquery_ui',
      'link',
      'menu',
      'node',
      'nodereference',
      'openid',
      'optionwidgets',
      'path',
      'php',
      'ping',
      'poll',
      'profile',
      'search',
      'system',
      'taxonomy',
      'text',
      'throttle',
      'tracker',
      'translation',
      'trigger',
      'uc_attribute',
      'uc_flatrate',
      'uc_order',
      'uc_product',
      'uc_store',
      'upload',
      'user',
      'userreference',
      'variable',
      'variable_admin',
      'views_export',
      // Include modules that do not have an upgrade path, defined in the
      // $noUpgradePath property in MigrateUpgradeForm.
      'date_api',
      'date_popup',
      'number',
      'views_ui',
    ];
    // TODO: remove after 8.5 is sunset.
    // See https://www.drupal.org/project/commerce_migrate/issues/2976114
    $version = _install_get_version_info(\Drupal::VERSION);
    if ($version['minor'] == 5) {
      $paths[] = 'i18nmenu';
    }
    return $paths;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    $paths = [
      'aggregator',
      'book',
      'ca',
      'date_copy',
      'devel',
      'devel_generate',
      'devel_node_access',
      'forum',
      'i18n',
      'i18nblocks',
      'i18ncck',
      'i18ncontent',
      'i18nmenu',
      'i18npoll',
      'i18nprofile',
      'i18ntaxonomy',
      'i18nviews',
      'locale',
      'phone',
      'statistics',
      'syslog',
      'test_gateway',
      'token',
      'tokenSTARTER',
      'token_actions',
      'uc_2checkout',
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
    // TODO: remove after 8.5 is sunset.
    // See https://www.drupal.org/project/commerce_migrate/issues/2976114
    $version = _install_get_version_info(\Drupal::VERSION);
    if ($version['minor'] == 5) {
      $key = array_search('i18nmenu', $paths);
      unset($paths[$key]);
    }
    return $paths;
  }

}
