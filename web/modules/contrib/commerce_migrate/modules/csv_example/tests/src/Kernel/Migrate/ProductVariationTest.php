<?php

namespace Drupal\Tests\commerce_migrate_csv_example\Kernel\Migrate;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests Product migration.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate_csv_example
 */
class ProductVariationTest extends CsvTestBase {

  use CommerceMigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'action',
    'address',
    'commerce',
    'commerce_migrate_csv_example',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'entity',
    'field',
    'file',
    'image',
    'inline_entity_form',
    'migrate_source_csv',
    'options',
    'path',
    'system',
    'taxonomy',
    'text',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $fixtures = [__DIR__ . '/../../../fixtures/csv/example-products.csv'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installConfig('commerce_product');

    $this->fs = \Drupal::service('file_system');
    $this->installEntitySchema('user');
    // Copy the source files.
    $this->fileMigrationSetup(__DIR__ . '/../../../fixtures/images');
    $this->createAttribute(['Accessory Size', 'Color', 'Shoe Size', 'Size']);

    $images = ['product_image', 'product_image_2', 'product_image_3'];
    foreach ($images as $image) {
      $field_name = 'field_' . $image;
      $field_storage_definition = [
        'field_name' => $field_name,
        'entity_type' => 'commerce_product_variation',
        'type' => 'image',
        'cardinality' => 1,
      ];
      $storage = FieldStorageConfig::create($field_storage_definition);
      $storage->save();

      $field_instance = [
        'field_name' => $field_name,
        'entity_type' => 'commerce_product_variation',
        'bundle' => 'default',
        'label' => $image,
        'settings' => [
          'handler' => 'default:file',
        ],
      ];
      $field = FieldConfig::create($field_instance);
      $field->save();
    }
  }

  /**
   * Test product variation migration from CSV source file.
   */
  public function testProductVariation() {
    $this->executeMigrations([
      'csv_example_attribute_value',
      'csv_example_image',
      'csv_example_product_variation',
    ]);

    // Set the attribute and files array for testing. Before each variation
    // test these are modified as needed for that variation.
    $attributes = [
      'attribute_color' =>
        [
          'id' => '16',
          'value' => 'Black',
        ],
      'attribute_size' =>
        [
          'id' => '18',
          'value' => 'XS',
        ],
      'attribute_accessory_size' =>
        [
          'id' => '15',
          'value' => NULL,
        ],
      'attribute_shoe_size' =>
        [
          'id' => '17',
          'value' => NULL,
        ],
    ];

    $files = [
      'field_product_image' =>
        [
          'target_id' => '1',
          'alt' => '',
          'title' => '',
          'width' => '322',
          'height' => '156',
        ],
      'field_product_image_2' =>
        [
          'target_id' => '2',
          'alt' => '',
          'title' => '',
          'width' => '211',
          'height' => '239',
        ],
      'field_product_image_3' => [],
    ];
    $this->assertProductVariationEntity(1, 'default', '0', 'HE-058', '299.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(1, $attributes, $files);

    $attributes['attribute_size']['id'] = '19';
    $attributes['attribute_size']['value'] = 'SM';
    $files['field_product_image'] =
      [
        'target_id' => '6',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $files['field_product_image_2'] = [];
    $this->assertProductVariationEntity(2, 'default', '0', 'HE-059', '299.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(2, $attributes, $files);

    $attributes['attribute_size']['id'] = '20';
    $attributes['attribute_size']['value'] = 'MD';
    $files['field_product_image'] =
      [
        'target_id' => '7',
        'alt' => '',
        'title' => '',
        'width' => '225',
        'height' => '225',
      ];
    $this->assertProductVariationEntity(3, 'default', '0', 'HE-060', '299.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(3, $attributes, $files);

    $attributes['attribute_size']['id'] = '21';
    $attributes['attribute_size']['value'] = 'LG';
    $files['field_product_image']['target_id'] = '8';
    $this->assertProductVariationEntity(4, 'default', '0', 'HE-061', '299.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(4, $attributes, $files);

    $attributes['attribute_size']['id'] = '22';
    $attributes['attribute_size']['value'] = 'XL';
    $files['field_product_image'] =
      [
        'target_id' => '9',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $this->assertProductVariationEntity(5, 'default', '0', 'HE-062', '299.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(5, $attributes, $files);

    $attributes['attribute_size']['id'] = '23';
    $attributes['attribute_size']['value'] = '2XL';
    $files['field_product_image'] =
      [
        'target_id' => '10',
        'alt' => '',
        'title' => '',
        'width' => '225',
        'height' => '225',
      ];
    $this->assertProductVariationEntity(6, 'default', '0', 'HE-063', '299.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(6, $attributes, $files);

    $attributes['attribute_size']['id'] = '24';
    $attributes['attribute_size']['value'] = '3XL';
    $files['field_product_image'] =
      [
        'target_id' => '11',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $this->assertProductVariationEntity(7, 'default', '0', 'HE-064', '299.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(7, $attributes, $files);

    $attributes['attribute_color']['id'] = '25';
    $attributes['attribute_color']['value'] = 'Blue';
    $attributes['attribute_size']['id'] = '18';
    $attributes['attribute_size']['value'] = 'XS';
    $files['field_product_image']['target_id'] = '12';
    $this->assertProductVariationEntity(8, 'default', '0', 'HE-065', '299.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(8, $attributes, $files);

    $attributes['attribute_color']['id'] = '16';
    $attributes['attribute_color']['value'] = 'Black';
    $files['field_product_image'] =
      [
        'target_id' => '13',
        'alt' => '',
        'title' => '',
        'width' => '225',
        'height' => '225',
      ];
    $this->assertProductVariationEntity(9, 'default', '0', 'HE-072', '299.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(9, $attributes, $files);

    $attributes['attribute_size']['id'] = '19';
    $attributes['attribute_size']['value'] = 'SM';
    $files['field_product_image']['target_id'] = '14';
    $this->assertProductVariationEntity(10, 'default', '0', 'HE-073', '349.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(10, $attributes, $files);

    $attributes['attribute_size']['id'] = '24';
    $attributes['attribute_size']['value'] = '3XL';
    $files['field_product_image'] =
      [
        'target_id' => '15',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $this->assertProductVariationEntity(11, 'default', '0', 'HE-080', '349.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(11, $attributes, $files);

    $attributes['attribute_color']['id'] = '16';
    $attributes['attribute_color']['value'] = 'Black';
    $attributes['attribute_size']['id'] = '26';
    $attributes['attribute_size']['value'] = '4';
    $files['field_product_image']['target_id'] = '16';
    $this->assertProductVariationEntity(12, 'default', '0', 'HE-081', '399.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(12, $attributes, $files);

    $attributes['attribute_color']['id'] = '27';
    $attributes['attribute_color']['value'] = 'Purple';
    $attributes['attribute_size']['id'] = '28';
    $attributes['attribute_size']['value'] = '6';
    $files['field_product_image'] =
      [
        'target_id' => '17',
        'alt' => '',
        'title' => '',
        'width' => '225',
        'height' => '225',
      ];
    $this->assertProductVariationEntity(13, 'default', '0', 'HE-088', '299.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(13, $attributes, $files);

    $attributes['attribute_color']['id'] = '16';
    $attributes['attribute_color']['value'] = 'Black';
    $attributes['attribute_size']['id'] = '29';
    $attributes['attribute_size']['value'] = '8';
    $files['field_product_image']['target_id'] = '18';
    $this->assertProductVariationEntity(14, 'default', '0', 'HE-095', '299.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(14, $attributes, $files);

    $attributes['attribute_color']['id'] = '27';
    $attributes['attribute_color']['value'] = 'Purple';
    $attributes['attribute_size']['id'] = '30';
    $attributes['attribute_size']['value'] = '10';
    $files['field_product_image']['target_id'] = '19';
    $this->assertProductVariationEntity(15, 'default', '0', 'HE-102', '349.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(15, $attributes, $files);

    $attributes = [];
    $files['field_product_image'] =
      [
        'target_id' => '20',
        'alt' => '',
        'title' => '',
        'width' => '225',
        'height' => '225',
      ];
    $files['field_product_image_2'] =
      [
        'target_id' => '2',
        'alt' => '',
        'title' => '',
        'width' => '211',
        'height' => '239',
      ];
    $this->assertProductVariationEntity(16, 'default', '0', 'MC-01', '349.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(16, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '22',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $files['field_product_image_2'] = [];
    $this->assertProductVariationEntity(17, 'default', '0', 'MC-03', '25.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(17, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '24',
        'alt' => '',
        'title' => '',
        'width' => '225',
        'height' => '225',
      ];
    $this->assertProductVariationEntity(18, 'default', '0', 'MC-04', '14.990000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(18, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '25',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $this->assertProductVariationEntity(19, 'default', '0', 'MC-05', '9.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(19, $attributes, $files);

    $files['field_product_image']['target_id'] = '26';
    $this->assertProductVariationEntity(20, 'default', '0', 'MC-06', '10.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(20, $attributes, $files);

    $files['field_product_image']['target_id'] = '27';
    $this->assertProductVariationEntity(21, 'default', '0', 'MC-07', '10.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(21, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '28',
        'alt' => '',
        'title' => '',
        'width' => '225',
        'height' => '225',
      ];
    $this->assertProductVariationEntity(22, 'default', '0', 'MC-08', '6.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(22, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '29',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $this->assertProductVariationEntity(23, 'default', '0', 'MC-09', '7.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(23, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '30',
        'alt' => '',
        'title' => '',
        'width' => '225',
        'height' => '225',
      ];
    $this->assertProductVariationEntity(24, 'default', '0', 'MC-10', '11.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(24, $attributes, $files);

    $files['field_product_image']['target_id'] = '31';
    $this->assertProductVariationEntity(25, 'default', '0', 'MC-12', '6.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(25, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '32',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $this->assertProductVariationEntity(26, 'default', '0', 'MC-13', '14.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(26, $attributes, $files);

    $files['field_product_image']['target_id'] = '33';
    $this->assertProductVariationEntity(27, 'default', '0', 'GO-01', '5.950000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(27, $attributes, $files);

    $files['field_product_image']['target_id'] = '34';
    $files['field_product_image_3'] =
      [
        'target_id' => '35',
        'alt' => '',
        'title' => '',
        'width' => '88',
        'height' => '100',
      ];
    $this->assertProductVariationEntity(28, 'default', '0', 'GO-50', '399.000000', 'CAD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntityAdditions(28, $attributes, $files);
  }

  /**
   * Asserts additions to a product variation.
   *
   * @param int $id
   *   The product variation id.
   * @param array $attributes
   *   Array of attribute names and id.
   * @param array $files
   *   Array of file information.
   */
  public function assertProductVariationEntityAdditions($id, array $attributes, array $files) {
    $variation = ProductVariation::load($id);
    foreach ($attributes as $name => $data) {
      if ($data) {
        $this->assertSame($data['id'], $variation->getAttributeValueId($name));
        $this->assertSame($data['value'], $variation->getAttributeValue($name)
          ->getName());
      }
    }
    foreach ($files as $name => $data) {
      if ($data) {
        $this->assertSame([$data], $variation->get($name)
          ->getValue(), "File data for $name is incorrect.");
      }
      else {
        $this->assertSame($data, $variation->get($name)->getValue());
      }
    }
  }

}
