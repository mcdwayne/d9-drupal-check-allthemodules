<?php

namespace Drupal\Tests\term_csv_tree_import\kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;
use Drupal\term_csv_tree_import\Service\CollectCsvData;
use org\bovigo\vfs\vfsStream;

/**
 * Test to ensure csv import is working with child element and custom fields.
 *
 * @group term_csv_tree_import
 */
class TermKernelTest extends KernelTestBase {
  use TaxonomyTestTrait;
  /**
   * Service to test.
   *
   * @var \Drupal\term_csv_tree_import\Service\CollectCsvData
   */
  protected $csvService;

  /**
   * Virtual file system.
   *
   * @var string
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'user', 'text', 'field'];

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Setup file and vocabulary.
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    // Sample data.
    $structure = [
      'csv' => [
        'input.csv' => "Parent,Custom_field_cntry_id,Child,Custom_field_cntry_state_id\nAruba,ABW,test,test2\nAfganistan,AFG,tes,test2"
      ]
    ];
    // Setup virtual file system.
    $this->fileSystem = vfsStream::setup('root', NULL, $structure);
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->csvService = new CollectCsvData($this->entityTypeManager);
  }

  /**
   * Test data in csv after file upload.
   */
  public function testCsvImportService() {
    $file = $this->fileSystem->url() . '/csv/input.csv';
    // Create vocabulary.
    $vocabulary = $this->createVocabulary()->id();
    // Create custom field and attach to vocabulary.
    FieldStorageConfig::create(array(
      'field_name' => 'field_cntry_id',
      'entity_type' => 'taxonomy_term',
      'type' => 'string',
      'settings' => array(
        'max_length' => 255,
        'is_ascii' => FALSE,
        'case_sensitive' => FALSE,
      ),
      'module' => 'core',
      'locked' => FALSE,
      'cardinality' => 1,
    ))->save();

    FieldConfig::create(array(
      'field_name' => 'field_cntry_id',
      'entity_type' => 'taxonomy_term',
      'bundle' => $vocabulary,
      'label' => 'Country id',
      'required' => TRUE,
    ))->save();

    // Custom field.
    // Create custom field and attach to vocabulary.
    FieldStorageConfig::create(array(
      'field_name' => 'field_cntry_state_id',
      'entity_type' => 'taxonomy_term',
      'type' => 'string',
      'settings' => array(
        'max_length' => 255,
        'is_ascii' => FALSE,
        'case_sensitive' => FALSE,
      ),
      'module' => 'core',
      'locked' => FALSE,
      'cardinality' => 1,
    ))->save();

    FieldConfig::create(array(
      'field_name' => 'field_cntry_state_id',
      'entity_type' => 'taxonomy_term',
      'bundle' => $vocabulary,
      'label' => 'Country id',
      'required' => TRUE,
    ))->save();

    $result = $this->csvService->loadData($file, $vocabulary);

    $this->assertEquals('Imported 2 terms.', $result);

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary);
    // Count elements in every tree depth.
    foreach ($terms as $element) {
      if (!isset($depth_count[$element->depth])) {
        $depth_count[$element->depth] = 0;
      }
      $depth_count[$element->depth]++;
    }
    $this->assertEqual(2, $depth_count[0], 'Two elements in taxonomy tree depth 0.');
    $this->assertEqual(2, $depth_count[1], 'Two elements in taxonomy tree depth 1.');
  }

}
