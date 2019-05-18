<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Functional\uc6;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;

/**
 * Tests adding a product to the cart after a full migration.
 *
 * @requires module migrate_plus
 * @requires module commerce_shipping
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class MigrateUpgradeAddToCartTest extends MigrateUpgradeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'address',
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
    $this->loadFixture(drupal_get_path('module', 'commerce_migrate_ubercart') . '/tests/fixtures/uc6.php');
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  public function testMigrateUpgrade() {
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $session = $this->assertSession();

    $driver = $connection_options['driver'];
    $connection_options['prefix'] = $connection_options['prefix']['default'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    $drivers = drupal_get_database_types();
    $form = $drivers[$driver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    $edit = [
      $driver => $connection_options,
      'source_private_file_path' => $this->getSourceBasePath(),
      'version' => $version,
    ];
    if ($version == 6) {
      $edit['d6_source_base_path'] = $this->getSourceBasePath();
    }
    else {
      $edit['source_base_path'] = $this->getSourceBasePath();
    }
    if (count($drivers) !== 1) {
      $edit['driver'] = $driver;
    }
    $edits = $this->translatePostValues($edit);

    // Start the upgrade process.
    $this->drupalGet('/upgrade');

    $this->drupalPostForm(NULL, [], t('Continue'));
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    $this->drupalPostForm(NULL, $edits, t('Review upgrade'));
    $session->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [], t('Perform upgrade'));

    // Add to cart.
    \Drupal::service('commerce_cart.cart_provider')->createCart('default');
    $this->drupalGet('/product/2');
    $this->drupalPostForm(NULL, [], t('Add to cart'));
    $session->pageTextContains('Beach Towel added to your cart.');
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
    return [];
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
