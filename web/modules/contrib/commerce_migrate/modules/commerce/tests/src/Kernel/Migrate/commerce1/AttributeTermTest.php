<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Tests attribute value migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class AttributeTermTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'datetime',
    'image',
    'link',
    'menu_ui',
    'migrate_plus',
    'node',
    'path',
    'profile',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('profile');
    // Setup files needed for the taxonomy_term:collection migration.
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->container->get('stream_wrapper_manager')->registerWrapper('public', PublicStream::class, StreamWrapperInterface::NORMAL);
    $fs = \Drupal::service('file_system');
    // The public file directory active during the test will serve as the
    // root of the fictional Drupal 7 site we're migrating.
    $fs->mkdir('public://sites/default/files', NULL, TRUE);

    $file_paths = [
      'collection-banner-to_wear.jpg',
      'collection-banner-to_carry.jpg',
      'collection-banner-to_drink_with.jpg',
      'collection-banner-to_geek_out.jpg',
    ];
    foreach ($file_paths as $file_path) {
      $filename = 'public://sites/default/files/' . $file_path;
      file_put_contents($filename, str_repeat('*', 8));
    }
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->getMigration('d7_file');
    // Set the source plugin's source_base_path configuration value, which
    // would normally be set by the user running the migration.
    $source = $migration->getSourceConfiguration();
    $source['constants']['source_base_path'] = $fs->realpath('public://');
    $migration->set('source', $source);
    $this->executeMigration($migration);

    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('commerce_product_attribute_value');

    $this->migrateFields();
    $this->executeMigrations([
      'd7_taxonomy_term',
    ]);
  }

  /**
   * Test attribute migrations from Commerce 1.
   */
  public function testMigrateProductAttributeValueTest() {
    $this->assertProductAttributeValueEntity('1', 'top_size', 'Small', 'Small', '0');
    $this->assertProductAttributeValueEntity('2', 'top_size', 'Medium', 'Medium', '0');
    $this->assertProductAttributeValueEntity('3', 'top_size', 'Large', 'Large', '0');

    $this->assertProductAttributeValueEntity('4', 'storage_capacity_with_very_long_', '8GB', '8GB', '0');
    $this->assertProductAttributeValueEntity('5', 'storage_capacity_with_very_long_', '16GB', '16GB', '1');
    $this->assertProductAttributeValueEntity('6', 'storage_capacity_with_very_long_', '32GB', '32GB', '2');

    $this->assertProductAttributeValueEntity('7', 'shoe_size', 'Mens 4/5 (Womens 5/6)', 'Mens 4/5 (Womens 5/6)', '0');
    $this->assertProductAttributeValueEntity('8', 'shoe_size', 'Mens 6 (Womens 7/8)', 'Mens 6 (Womens 7/8)', '0');
    $this->assertProductAttributeValueEntity('9', 'shoe_size', 'Mens 7/8 (Womens 9/10)', 'Mens 7/8 (Womens 9/10)', '0');
    $this->assertProductAttributeValueEntity('10', 'shoe_size', 'Mens 9 (Womens 11/12)', 'Mens 9 (Womens 11/12)', '0');
    $this->assertProductAttributeValueEntity('11', 'shoe_size', 'Mens 10/11', 'Mens 10/11', '0');
    $this->assertProductAttributeValueEntity('12', 'shoe_size', 'Mens 12', 'Mens 12', '0');
    $this->assertProductAttributeValueEntity('13', 'shoe_size', 'Mens 4 (Womens 6)', 'Mens 4 (Womens 6)', '0');
    $this->assertProductAttributeValueEntity('14', 'shoe_size', 'Mens 5 (Womens 7)', 'Mens 5 (Womens 7)', '0');
    $this->assertProductAttributeValueEntity('15', 'shoe_size', 'Mens 6 (Womens 8)', 'Mens 6 (Womens 8)', '0');
    $this->assertProductAttributeValueEntity('16', 'shoe_size', 'Mens 7 (Womens 9)', 'Mens 7 (Womens 9)', '0');
    $this->assertProductAttributeValueEntity('17', 'shoe_size', 'Mens 8 (Womens 10)', 'Mens 8 (Womens 10)', '0');
    $this->assertProductAttributeValueEntity('18', 'shoe_size', 'Mens 9 (Womens 11)', 'Mens 9 (Womens 11)', '0');
    $this->assertProductAttributeValueEntity('19', 'shoe_size', 'Mens 10 (Womens 12)', 'Mens 10 (Womens 12)', '0');
    $this->assertProductAttributeValueEntity('20', 'shoe_size', 'Mens 11', 'Mens 11', '0');
    $this->assertProductAttributeValueEntity('21', 'shoe_size', 'Mens 12', 'Mens 12', '0');

    $this->assertProductAttributeValueEntity('22', 'hat_size', 'One Size', 'One Size', '0');

    $this->assertProductAttributeValueEntity('23', 'color', 'Green', 'Green', '0');
    $this->assertProductAttributeValueEntity('24', 'color', 'Blue', 'Blue', '0');
    $this->assertProductAttributeValueEntity('25', 'color', 'Black', 'Black', '0');
    $this->assertProductAttributeValueEntity('26', 'color', 'Yellow', 'Yellow', '0');
    $this->assertProductAttributeValueEntity('27', 'color', 'Silver', 'Silver', '0');
    $this->assertProductAttributeValueEntity('28', 'color', 'Gray', 'Gray', '0');
    $this->assertProductAttributeValueEntity('29', 'color', 'Red', 'Red', '0');
    $this->assertProductAttributeValueEntity('30', 'color', 'Purple', 'Purple', '0');
    $this->assertProductAttributeValueEntity('31', 'color', 'Cream', 'Cream', '0');
    $this->assertProductAttributeValueEntity('32', 'color', 'Light Blue', 'Light Blue', '0');
    $this->assertProductAttributeValueEntity('33', 'color', 'Orange', 'Orange', '0');
    $this->assertProductAttributeValueEntity('34', 'color', 'Fuchia', 'Fuchia', '0');
    $this->assertProductAttributeValueEntity('35', 'color', 'Pink', 'Pink', '0');

    $this->assertProductAttributeValueEntity('36', 'bag_size', 'One Size', 'One Size', '0');
    $this->assertProductAttributeValueEntity('37', 'bag_size', '13"', '13"', '0');
    $this->assertProductAttributeValueEntity('38', 'bag_size', '15"', '15"', '0');
    $this->assertProductAttributeValueEntity('39', 'bag_size', '17"', '17"', '0');
  }

}
