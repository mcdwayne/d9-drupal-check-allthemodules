<?php

namespace Drupal\Tests\commerce_export\Kernel\Migrate;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests Product migration.
 *
 * @requires module entity_reference_revisions
 * @requires module migrate_source_csv
 * @requires module paragraphs
 *
 * @group commerce_export
 */
class ProductTest extends TestBase {

  use MigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'file',
    'path',
    'taxonomy',
    'commerce_product',
    'paragraphs',
    'entity_reference_revisions',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->createAttribute();
    $this->createVocabularies();
    $vocabularies = [
      'Category',
      'Season',
    ];
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
    $this->enableModules(['commerce_export']);
    $this->executeMigrations([
      'import_taxonomy_term',
      'import_image',
      'import_attribute_value',
      'import_product_variation',
      'import_paragraph_cta',
      'import_paragraph_tab',
      'import_video',
      'import_product',
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
    $this->assertProductEntity(1, '0', 'TherMaxx', TRUE, ['1'], $variations, $terms, $suggested);

    $terms['field_season'] = [['target_id' => '3']];
    $this->assertProductEntity(2, '0', 'Aquaseal', TRUE, ['1'], ['16', '18'], $terms, $suggested);

    $terms['field_category'] = [['target_id' => '4']];
    $terms['field_season'] = [['target_id' => '2']];
    $this->assertProductEntity(3, '0', 'Zip Care', TRUE, ['1'], ['17'], $terms, $suggested);

    $suggested = [
      ['target_id' => '1'],
      ['target_id' => '3'],
      ['target_id' => '2'],
    ];
    $this->assertProductEntity(4, '0', 'Wetsuit Shampoo', TRUE, ['1'], ['19'], $terms, $suggested);

    $suggested = [];
    $this->assertProductEntity(5, '0', 'BC Life', TRUE, ['1'], ['20'], $terms, $suggested);
    $this->assertProductEntity(6, '0', 'MiraZyme', TRUE, ['1'], ['21', '23'], $terms, $suggested);
    $this->assertProductEntity(7, '0', 'Sea Quick', TRUE, ['1'], ['26'], $terms, $suggested);

    $terms['field_category'] = [['target_id' => '5']];
    $terms['field_season'] = [['target_id' => '6']];
    $this->assertProductEntity(8, '0', 'GoPro 4', TRUE, ['1'], ['27'], $terms, $suggested);

    $suggested = [
      ['target_id' => '7'],
      ['target_id' => '8'],
    ];
    $terms['field_category'] = [['target_id' => '4']];
    $terms['field_season'] = [['target_id' => '2']];
    $this->assertProductEntity(9, '0', 'Sea Gold Anti-Fog', TRUE, ['1'], ['22'], $terms, $suggested);

    $suggested = [];
    $this->assertProductEntity(10, '0', 'Sea Buff', TRUE, ['1'], ['24'], $terms, $suggested);
    $this->assertProductEntity(11, '0', 'Sea Gold / Sea Buff Combo', TRUE, ['1'], ['25'], $terms, $suggested);

    $terms['field_category'] = [['target_id' => '5']];
    $terms['field_season'] = [['target_id' => '6']];
    $suggested = [['target_id' => '8']];
    $this->assertProductEntity(12, '0', 'Hero 5', TRUE, ['1'], ['28'], $terms, $suggested);
  }

}
