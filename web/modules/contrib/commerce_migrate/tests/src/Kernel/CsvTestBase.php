<?php

namespace Drupal\Tests\commerce_migrate\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\MigrateException;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Test base for migrations tests with CSV source file.
 *
 * Any migration using this test base must set the 'path' property to the same
 * as $csvPath, 'public://import'. The source test CSV file must be in
 * /tests/fixtures/csv and any source file to migrate, such as images, must be
 * in /test/fixtures/images.
 */
abstract class CsvTestBase extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'migrate_source_csv',
  ];

  /**
   * Basename of the directory used in the migration 'path:' configuration.
   *
   * The basename must be the same for all migrations in a test.
   *
   * @var string
   */
  protected $csvPath = 'public://import';

  /**
   * The relative path to each test fixture needed for the test.
   *
   * @var string|array
   */
  protected $fixtures;

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
    // Setup a public file directory for all migration source files.
    $this->fs = $this->container->get('file_system');
    $this->config('system.file')->set('default_scheme', 'public')->save();
    $this->loadFixture($this->getFixtureFilePath());
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return $this->fixtures;
  }

  /**
   * Copy the source CSV files to the path in the migration.
   *
   * @param string|array $fixtures
   *   The full pathname of the fixture.
   */
  protected function loadFixture($fixtures) {
    if (is_string($fixtures)) {
      $fixtures = [$fixtures];
    }

    // Make sure the file destination directory exists.
    if (!file_exists($this->csvPath)) {
      $this->fs->mkdir($this->csvPath, NULL, TRUE);
    }

    // Copy each fixture to the public directory.
    foreach ($fixtures as $fixture) {
      $filename = basename($fixture);
      $destination_uri = $this->csvPath . '/' . $filename;

      $file_system = \Drupal::service('file_system');
      if (!$file_system->copy($fixture, $destination_uri)) {
        throw new MigrateException("Migration setup failed to copy source CSV file '$fixture' to '$destination_uri'.");
      }
    }
  }

  /**
   * Creates attributes.
   *
   * @param array $attributes
   *   The attribute names to create.
   */
  protected function createAttribute(array $attributes) {
    if (is_array($attributes)) {
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
  }

  /**
   * Creates vocabularies.
   *
   * @param array $vids
   *   An array of vocabulary ids.
   * */
  protected function createVocabularies(array $vids) {
    if (is_array($vids)) {
      foreach ($vids as $vid) {
        $vocabulary = Vocabulary::create([
          'name' => $vid,
          'description' => $this->randomMachineName(),
          'vid' => mb_strtolower($vid),
          'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          'weight' => mt_rand(0, 10),
        ]);
        $vocabulary->save();
      }
    }
  }

  /**
   * Prepares a public file directory for the migration.
   *
   * Enables file module and recursively copies the source directory to the
   * migration source path.
   *
   * @param string $source_directory
   *   The source file directory.
   */
  protected function fileMigrationSetup($source_directory) {
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->container->get('stream_wrapper_manager')
      ->registerWrapper('public', PublicStream::class, StreamWrapperInterface::NORMAL);
    // Copy the file source directory to the public directory.
    $destination = $this->csvPath . '/images';
    $this->recurseCopy($source_directory, $destination);
  }

  /**
   * Helper to copy directory tree.
   *
   * @param string $src
   *   The source path.
   * @param string $dst
   *   The destination path.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  private function recurseCopy($src, $dst) {
    $dir = opendir($src);
    if (!file_exists($dst)) {
      $this->fs->mkdir($dst, NULL, TRUE);
    }
    $file_system = \Drupal::service('file_system');
    while (FALSE !== ($file = readdir($dir))) {
      if (($file != '.') && ($file != '..')) {
        if (is_dir($src . '/' . $file)) {
          $this->recurseCopy($src . '/' . $file, $dst . '/' . $file);
        }
        else {
          if (!$file_system->copy($src . '/' . $file, $dst . '/' . $file)) {
            throw new MigrateException("Migration setup failed to copy source CSV file '$fixture' to '$destination_uri'.");
          }
        }
      }
    }
    closedir($dir);
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

}
