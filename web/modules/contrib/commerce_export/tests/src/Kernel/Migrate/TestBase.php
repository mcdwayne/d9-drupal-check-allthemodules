<?php

namespace Drupal\Tests\commerce_export\Kernel\Migrate;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Test base for migrations tests.
 */
abstract class TestBase extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'action',
    'address',
    'entity',
    'field',
    'inline_entity_form',
    'options',
    'system',
    'text',
    'user',
    'views',
    'commerce',
    'commerce_price',
    'commerce_store',
    'migrate_source_csv',
    'commerce_export',
  ];

  /**
   * Filename of the test fixture.
   *
   * @var string
   */
  public static $fixtureFile = 'product.csv';

  /**
   * File system active during the test.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fs;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    KernelTestBase::setUp();
    $this->fs = \Drupal::service('file_system');
    $this->installEntitySchema('user');
    $this->installEntitySchema('commerce_store');
    $this->createDefaultStore();
    $this->copyFiles();
  }

  /**
   * Copy files for the tests.
   *
   * Copy the migrations to ./migrations for the tests to  avoid schema errors
   * when loading the migrations. Copy the source CSV to the path in the
   * migrations.
   */
  protected function copyFiles() {
    // Copy all migrations.
    $destination = __DIR__ . '/../../../../migrations';
    if (!file_exists($destination)) {
      $this->fs->mkdir($destination, NULL, TRUE);
    }

    $source_directory = __DIR__ . '/../../../../config/install/';
    $mask = '/^((?!group).)*$/';
    $files = file_scan_directory($source_directory, $mask);
    foreach ($files as $file) {
      $destination_uri = $destination . '/' . $file->filename;
      file_unmanaged_copy($file->uri, $destination_uri);
    }

    // Make sure the file destination directory exists.
    $destination = 'public://import';
    if (!file_exists($destination)) {
      $this->fs->mkdir($destination, NULL, TRUE);
    }

    // Copy fixture.
    $source = __DIR__ . '/../../../fixtures/csv/' . self::$fixtureFile;
    $destination_uri = $destination . '/' . self::$fixtureFile;
    file_unmanaged_copy($source, $destination_uri);
  }

  /**
   * Creates a default store.
   */
  protected function createDefaultStore() {
    $currency_importer = \Drupal::service('commerce_price.currency_importer');
    /** @var \Drupal\commerce_store\StoreStorage $store_storage */
    $store_storage = \Drupal::service('entity_type.manager')
      ->getStorage('commerce_store');

    $currency_importer->import('USD');
    $store_values = [
      'type' => 'default',
      'uid' => 1,
      'name' => 'Demo store',
      'mail' => 'admin@example.com',
      'address' => [
        'country_code' => 'US',
      ],
      'default_currency' => 'USD',
    ];

    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    $store = $store_storage->create($store_values);
    $store->save();
    $store_storage->markAsDefault($store);
  }

  /**
   * Creates attributes.
   */
  protected function createAttribute() {
    $attributes = [
      'Accessory Size',
      'Color',
      'Shoe Size',
      'Size',
    ];
    foreach ($attributes as $attribute) {
      $id = strtolower($attribute);
      $id = preg_replace('/[^a-z0-9_]+/', '_', $id);
      preg_replace('/_+/', '_', $id);
      $field_name = 'attribute_' . $id;
      $field_storage_definition = [
        'field_name' => $field_name,
        'entity_type' => 'commerce_product_variation',
        'type' => 'entity_reference',
        'cardinality' => 1,
        'settings' => ['target_type' => 'commerce_product_attribute_value'],
      ];
      $storage = FieldStorageConfig::create($field_storage_definition);
      $storage->save();

      $field_instance = [
        'field_name' => $field_name,
        'entity_type' => 'commerce_product_variation',
        'bundle' => 'default',
        'label' => $attribute,
        'settings' => [
          'handler' => 'default:commerce_product_attribute_value',
          'handler_settings' => [
            'target_bundles' => [$id],
          ],
        ],
      ];
      $field = FieldConfig::create($field_instance);
      $field->save();
      $ret = ProductAttribute::create([
        'id' => strtolower($id),
        'label' => $attribute,
      ]);
      $ret->save();
    }
  }

  /**
   * Creates vocabularies..
   */
  protected function createVocabularies() {
    // Create a vocabularies.
    $vids = [
      'Category',
      'Season',
    ];
    foreach ($vids as $vid) {
      $vocabulary = Vocabulary::create([
        'name' => $vid,
        'description' => $this->randomMachineName(),
        'vid' => Unicode::strtolower($vid),
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        'weight' => mt_rand(0, 10),
      ]);
      $vocabulary->save();
    }
  }

  /**
   * Prepare the file migration for running.
   */
  protected function fileMigrationSetup() {
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->container->get('stream_wrapper_manager')
      ->registerWrapper('public', PublicStream::class, StreamWrapperInterface::NORMAL);

    // The public file directory active during the test will serve as the
    // source for the image files.
    // @todo: Get the directory from the migration
    $destination = 'public://images';
    if (!file_exists($destination)) {
      $this->fs->mkdir($destination, NULL, TRUE);
    }

    // Copy all test source files to source directory used in migration.
    $source_directory = __DIR__ . '/../../../fixtures/images/';
    $mask = '/.*/';
    $files = file_scan_directory($source_directory, $mask);
    foreach ($files as $file) {
      $destination_uri = $destination . '/' . $file->filename;
      file_unmanaged_copy($file->uri, $destination_uri);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $dst = __DIR__ . '/../../../../migrations';

    foreach (new \DirectoryIterator($dst) as $fileInfo) {
      if (!$fileInfo->isDot()) {
        unlink($fileInfo->getPathname());
      }
    }
    rmdir($dst);
    parent::tearDown();
  }

}
