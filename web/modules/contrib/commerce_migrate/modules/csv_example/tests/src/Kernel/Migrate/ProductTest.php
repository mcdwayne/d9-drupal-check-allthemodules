<?php

namespace Drupal\Tests\commerce_migrate_csv_example\Kernel\Migrate;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;
use Drupal\commerce_product\Entity\Product;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests Product migration.
 *
 * @requires module entity_reference_revisions
 * @requires module migrate_source_csv
 * @requires module paragraphs
 *
 * @group commerce_migrate_csv_example
 */
class ProductTest extends CsvTestBase {

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
    'entity_reference_revisions',
    'field',
    'file',
    'inline_entity_form',
    'migrate_source_csv',
    'options',
    'paragraphs',
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
    $this->installEntitySchema('user');
    $this->installEntitySchema('commerce_store');
    $this->createDefaultStore();
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installConfig('commerce_product');

    $this->createAttribute(['Accessory Size', 'Color', 'Shoe Size', 'Size']);
    $vocabularies = [
      'Category',
      'Season',
    ];
    $this->createVocabularies($vocabularies);

    foreach ($vocabularies as $vocabulary) {
      $id = strtolower($vocabulary);
      $id = preg_replace('/[^a-z0-9_]+/', '_', $id);
      preg_replace('/_+/', '_', $id);
      $field_name = 'field_' . $id;
      $field_storage_definition = [
        'field_name' => $field_name,
        'entity_type' => 'commerce_product',
        'type' => 'entity_reference',
        'cardinality' => 3,
        'settings' => ['target_type' => 'taxonomy_term'],
      ];
      $storage = FieldStorageConfig::create($field_storage_definition);
      $storage->save();

      $field_instance = [
        'field_name' => $field_name,
        'entity_type' => 'commerce_product',
        'bundle' => 'default',
        'label' => $vocabulary,
        'settings' => [
          'handler' => 'default:taxonomy_term',
          'handler_settings' => [
            'target_bundles' => ['category' => 'category'],
          ],
        ],
      ];
      $field = FieldConfig::create($field_instance);
      $field->save();
    }

    $field_name = 'field_suggested_products';
    $field_storage_definition = [
      'field_name' => $field_name,
      'entity_type' => 'commerce_product',
      'type' => 'entity_reference',
      'cardinality' => 3,
      'settings' => ['target_type' => 'taxonomy_term'],
    ];
    $storage = FieldStorageConfig::create($field_storage_definition);
    $storage->save();

    $field_instance = [
      'field_name' => $field_name,
      'entity_type' => 'commerce_product',
      'bundle' => 'default',
      'label' => 'suggested',
      'field_type' => 'entity_reference',
      'settings' => [
        'handler' => 'default:commerce_product',
        'handler_settings' => [
          'target_bundles' => ['default' => 'default'],
        ],
      ],
    ];
    $field = FieldConfig::create($field_instance);
    $field->save();
  }

  /**
   * Test product migration from CSV source file.
   */
  public function testProduct() {
    $this->executeMigrations([
      'csv_example_taxonomy_term',
      'csv_example_image',
      'csv_example_attribute_value',
      'csv_example_product_variation',
      'csv_example_paragraph_cta',
      'csv_example_paragraph_with_paragraph_reference',
      'csv_example_product',
    ]);

    $variations = [
      '1',
      '2',
      '3',
      '4',
      '5',
      '6',
      '7',
      '8',
      '9',
      '10',
      '11',
      '12',
      '13',
      '14',
      '15',
    ];

    // Initialize the term  array for testing.
    $terms = [
      'field_category' => [
        ['target_id' => '1'],
      ],
      'field_season' => [
        ['target_id' => '2'],
      ],
    ];
    // Initialize the suggested products array for testing.
    $suggested = [];
    $this->assertProductEntity(1, 'default', '0', 'TherMaxx', TRUE, ['1'], $variations);
    $this->assertProductEntityAdditions(1, $terms, $suggested);

    $terms['field_season'] = [['target_id' => '3']];
    $this->assertProductEntity(2, 'default', '0', 'Aquaseal', TRUE, ['1'], [
      '16',
      '18',
    ]);
    $this->assertProductEntityAdditions(2, $terms, $suggested);

    $terms['field_category'] = [['target_id' => '4']];
    $terms['field_season'] = [['target_id' => '2']];
    $this->assertProductEntity(3, 'default', '0', 'Zip Care', TRUE, ['1'], ['17']);
    $this->assertProductEntityAdditions(3, $terms, $suggested);

    $suggested = [
      ['target_id' => '1'],
      ['target_id' => '3'],
      ['target_id' => '2'],
    ];
    $this->assertProductEntity(4, 'default', '0', 'Wetsuit Shampoo', TRUE, ['1'], ['19']);
    $this->assertProductEntityAdditions(4, $terms, $suggested);

    $suggested = [];
    $this->assertProductEntity(5, 'default', '0', 'BC Life', TRUE, ['1'], ['20']);
    $this->assertProductEntityAdditions(5, $terms, $suggested);
    $this->assertProductEntity(6, 'default', '0', 'MiraZyme', TRUE, ['1'], [
      '21',
      '23',
    ]);
    $this->assertProductEntityAdditions(6, $terms, $suggested);
    $this->assertProductEntity(7, 'default', '0', 'Sea Quick', TRUE, ['1'], ['26']);
    $this->assertProductEntityAdditions(7, $terms, $suggested);

    $terms['field_category'] = [['target_id' => '5']];
    $terms['field_season'] = [['target_id' => '6']];
    $this->assertProductEntity(8, 'default', '0', 'GoPro 4', TRUE, ['1'], ['27']);
    $this->assertProductEntityAdditions(8, $terms, $suggested);

    $suggested = [
      ['target_id' => '7'],
      ['target_id' => '8'],
    ];
    $terms['field_category'] = [['target_id' => '4']];
    $terms['field_season'] = [['target_id' => '2']];
    $this->assertProductEntity(9, 'default', '0', 'Sea Gold Anti-Fog', TRUE, ['1'], ['22']);
    $this->assertProductEntityAdditions(9, $terms, $suggested);

    $suggested = [];
    $this->assertProductEntity(10, 'default', '0', 'Sea Buff', TRUE, ['1'], ['24']);
    $this->assertProductEntityAdditions(10, $terms, $suggested);
    $this->assertProductEntity(11, 'default', '0', 'Sea Gold / Sea Buff Combo', TRUE, ['1'], ['25']);
    $this->assertProductEntityAdditions(11, $terms, $suggested);

    $terms['field_category'] = [['target_id' => '5']];
    $terms['field_season'] = [['target_id' => '6']];
    $suggested = [['target_id' => '8']];
    $this->assertProductEntity(12, 'default', '0', 'Hero 5', TRUE, ['1'], ['28']);
    $this->assertProductEntityAdditions(12, $terms, $suggested);
  }

  /**
   * Asserts additions to product.
   *
   * @param int $id
   *   The product id.
   * @param array $terms
   *   An array of taxonomy field names and values.
   * @param array $suggested
   *   An array of suggested products.
   */
  public function assertProductEntityAdditions($id, array $terms, array $suggested) {
    $product = Product::load($id);
    foreach ($terms as $name => $data) {
      $this->assertSame($data, $product->get($name)
        ->getValue(), "Taxonomy $name is incorrect.");
    }
    $this->assertSame($suggested, $product->get('field_suggested_products')
      ->getValue());
  }

}
