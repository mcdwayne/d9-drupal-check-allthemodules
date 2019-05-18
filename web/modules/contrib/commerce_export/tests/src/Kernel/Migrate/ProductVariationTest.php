<?php

namespace Drupal\Tests\commerce_export\Kernel\Migrate;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests Product migration.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_export
 */
class ProductVariationTest extends TestBase {

  use MigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'file',
    'image',
    'path',
    'taxonomy',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->fileMigrationSetup();
    $this->createAttribute();

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
    $this->enableModules(['commerce_export']);
    $this->executeMigrations([
      'import_attribute_value',
      'import_image',
      'import_product_variation',
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
    $this->assertProductVariationEntity(1, '0', 'HE-058', '299.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

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
    $this->assertProductVariationEntity(2, '0', 'HE-059', '299.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

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
    $this->assertProductVariationEntity(3, '0', 'HE-060', '299.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $attributes['attribute_size']['id'] = '21';
    $attributes['attribute_size']['value'] = 'LG';
    $files['field_product_image']['target_id'] = '8';
    $this->assertProductVariationEntity(4, '0', 'HE-061', '299.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

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
    $this->assertProductVariationEntity(5, '0', 'HE-062', '299.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

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
    $this->assertProductVariationEntity(6, '0', 'HE-063', '299.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

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
    $this->assertProductVariationEntity(7, '0', 'HE-064', '299.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $attributes['attribute_color']['id'] = '25';
    $attributes['attribute_color']['value'] = 'Blue';
    $attributes['attribute_size']['id'] = '18';
    $attributes['attribute_size']['value'] = 'XS';
    $files['field_product_image']['target_id'] = '12';
    $this->assertProductVariationEntity(8, '0', 'HE-065', '299.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

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
    $this->assertProductVariationEntity(9, '0', 'HE-072', '299.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $attributes['attribute_size']['id'] = '19';
    $attributes['attribute_size']['value'] = 'SM';
    $files['field_product_image']['target_id'] = '14';
    $this->assertProductVariationEntity(10, '0', 'HE-073', '349.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

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
    $this->assertProductVariationEntity(11, '0', 'HE-080', '349.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $attributes['attribute_color']['id'] = '16';
    $attributes['attribute_color']['value'] = 'Black';
    $attributes['attribute_size']['id'] = '26';
    $attributes['attribute_size']['value'] = '4';
    $files['field_product_image']['target_id'] = '16';
    $this->assertProductVariationEntity(12, '0', 'HE-081', '399.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

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
    $this->assertProductVariationEntity(13, '0', 'HE-088', '299.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $attributes['attribute_color']['id'] = '16';
    $attributes['attribute_color']['value'] = 'Black';
    $attributes['attribute_size']['id'] = '29';
    $attributes['attribute_size']['value'] = '8';
    $files['field_product_image']['target_id'] = '18';
    $this->assertProductVariationEntity(14, '0', 'HE-095', '299.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $attributes['attribute_color']['id'] = '27';
    $attributes['attribute_color']['value'] = 'Purple';
    $attributes['attribute_size']['id'] = '30';
    $attributes['attribute_size']['value'] = '10';
    $files['field_product_image']['target_id'] = '19';
    $this->assertProductVariationEntity(15, '0', 'HE-102', '349.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

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
    $this->assertProductVariationEntity(16, '0', 'MC-01', '349.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '22',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $files['field_product_image_2'] = [];
    $this->assertProductVariationEntity(17, '0', 'MC-03', '25.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '24',
        'alt' => '',
        'title' => '',
        'width' => '225',
        'height' => '225',
      ];
    $this->assertProductVariationEntity(18, '0', 'MC-04', '14.990000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '25',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $this->assertProductVariationEntity(19, '0', 'MC-05', '9.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image']['target_id'] = '26';
    $this->assertProductVariationEntity(20, '0', 'MC-06', '10.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image']['target_id'] = '27';
    $this->assertProductVariationEntity(21, '0', 'MC-07', '10.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '28',
        'alt' => '',
        'title' => '',
        'width' => '225',
        'height' => '225',
      ];
    $this->assertProductVariationEntity(22, '0', 'MC-08', '6.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '29',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $this->assertProductVariationEntity(23, '0', 'MC-09', '7.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '30',
        'alt' => '',
        'title' => '',
        'width' => '225',
        'height' => '225',
      ];
    $this->assertProductVariationEntity(24, '0', 'MC-10', '11.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image']['target_id'] = '31';
    $this->assertProductVariationEntity(25, '0', 'MC-12', '6.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image'] =
      [
        'target_id' => '32',
        'alt' => '',
        'title' => '',
        'width' => '322',
        'height' => '156',
      ];
    $this->assertProductVariationEntity(26, '0', 'MC-13', '14.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image']['target_id'] = '33';
    $this->assertProductVariationEntity(27, '0', 'GO-01', '5.950000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);

    $files['field_product_image']['target_id'] = '34';
    $files['field_product_image_3'] =
      [
        'target_id' => '35',
        'alt' => '',
        'title' => '',
        'width' => '88',
        'height' => '100',
      ];
    $this->assertProductVariationEntity(28, '0', 'GO-50', '399.000000', 'CAD', NULL, NULL, 'default', NULL, NULL, $attributes, $files);
  }

}
