<?php

namespace Drupal\Tests\entity_usage\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests basic usage tracking on generic entities referenced by HTML links.
 *
 * THIS IS A LEGACY TEST AND SHOULD NOT BE FURTHER IMPROVED OR EXTENDED.
 *
 * @group entity_usage
 *
 * @package Drupal\Tests\entity_usage\Kernel
 */
class EntityUsageLegacyHtmlLinkKernelTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'entity_usage', 'file', 'editor'];

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * The entity to be referenced in this test.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $referencedEntity;

  /**
   * The File entity to be referenced in this test.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $referencedFileEntity;

  /**
   * Some test entities.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $referencingEntities;

  /**
   * The injected database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $injectedDatabase;

  /**
   * The name of the table that stores entity usage information.
   *
   * @var string
   */
  protected $tableName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->injectedDatabase = $this->container->get('database');

    $this->installSchema('entity_usage', ['entity_usage']);
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->tableName = 'entity_usage';

    // Set up the text fields fields.
    FieldStorageConfig::create([
      'field_name' => 'field_text_long',
      'entity_type' => $this->entityType,
      'type' => 'text_long',
      'settings' => [],
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'field_name' => 'field_text_long',
      'label' => 'Text field',
    ])->save();
    FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ])->save();

    // Create the entity to be referenced.
    $this->referencedEntity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(['name' => $this->randomMachineName()]);
    $this->referencedEntity->save();

    // Create two test entities.
    $this->referencingEntities = $this->getReferencingEntities();
    $this->referencingEntities[0]->field_text_long = [
      'value' => '<p>Lorem ipsum 1</p>',
      'format' => 'full_html',
    ];
    $this->referencingEntities[0]->save();

    $this->referencingEntities[1]->field_text_long = [
      'value' => '<p>Lorem ipsum 2</p>',
      'format' => 'full_html',
    ];
    $this->referencingEntities[1]->save();

    // Create test File entity.
    $file_uri = 'public://eu_html_link_test_example.jpg';
    file_unmanaged_copy(\Drupal::root() . '/core/misc/druplicon.png', $file_uri);
    $this->referencedFileEntity = File::create(['uri' => $file_uri]);
    $this->referencedFileEntity->save();
  }

  /**
   * Tests basic entity tracking on test entities using entityreference fields.
   */
  public function testHtmlLinkTracking() {
    /** @var \Drupal\entity_usage\EntityUsage $entity_usage */
    $entity_usage = $this->container->get('entity_usage.usage');

    $referencing_entity = $this->referencingEntities[0];

    $test_entity_url = $this->referencedEntity->toUrl();
    $test_file_url = preg_replace('{^public://}', \Drupal::service('stream_wrapper.public')->getDirectoryPath() . '/', $this->referencedFileEntity->getFileUri());
    $absolute_test_file_url = \file_create_url($this->referencedFileEntity->getFileUri());

    // First check usage is 0 for the referenced entity.
    $usage = $entity_usage->listSources($this->referencedEntity);
    $this->assertSame([], $usage, 'Initial usage is correctly empty.');

    // Reference from entity to another entity and the file entity file.
    $referencing_entity->field_text_long = [
      'value' => '<p>Lorem ipsum 1 <a href="' . $test_entity_url->toString() . '">Link to test entity</a>. <a href="' . $test_file_url . '">Link to test file</a></p>',
      'format' => 'full_html',
    ];
    $referencing_entity->save();

    $referencing_entity_type = $referencing_entity->getEntityTypeId();
    $referencing_entity_1_id = $referencing_entity->id();
    $test_entity_usage = $entity_usage->listSources($this->referencedEntity);

    // Since we are not testing revisions here, the usage details are the same
    // for all the sources we want to test.
    $comparison_array = [
      [
        'source_langcode' => $referencing_entity->language()->getId(),
        'source_vid' => 0,
        'method' => 'html_link',
        'field_name' => 'field_text_long',
        'count' => '1',
      ],
    ];

    static::assertEquals([
      $referencing_entity_type => [
        $referencing_entity_1_id => $comparison_array,
      ],
    ], $test_entity_usage, 'The usage count for the test entity is correct.');
    $test_file_usage = $entity_usage->listSources($this->referencedFileEntity);
    static::assertEquals([
      $referencing_entity_type => [
        $referencing_entity_1_id => $comparison_array,
      ],
    ], $test_file_usage, 'The usage count for the test file is correct.');

    // Do the same on the other refencing test entity, but with absolute URL's.
    $current_request = \Drupal::request();
    $config = \Drupal::configFactory()->getEditable('entity_usage.settings');
    $config->set('site_domains', [$current_request->getHttpHost()]);
    $config->save();
    $referencing_entity = $this->referencingEntities[1];

    $referencing_entity->field_text_long = [
      'value' => '<p>Lorem ipsum 2 <a href="' . $test_entity_url->setAbsolute()->toString() . '">Link to test entity</a>. <a href="' . $absolute_test_file_url . '">Link to test file</a></p>',
      'format' => 'full_html',
    ];
    $referencing_entity->save();

    $referencing_entity_2_id = $referencing_entity->id();
    $test_entity_usage = $entity_usage->listSources($this->referencedEntity);
    static::assertEquals([
      $referencing_entity_type => [
        $referencing_entity_1_id => $comparison_array,
        $referencing_entity_2_id => $comparison_array,
      ],
    ], $test_entity_usage, 'The usage count for the test entity is correct.');
    $test_file_usage = $entity_usage->listSources($this->referencedFileEntity);
    static::assertEquals([
      $referencing_entity_type => [
        $referencing_entity_1_id => $comparison_array,
        $referencing_entity_2_id => $comparison_array,
      ],
    ], $test_file_usage, 'The usage count for the test file is correct.');
  }

  /**
   * Creates two test entities.
   *
   * @return array
   *   An array of entity objects.
   */
  protected function getReferencingEntities() {

    $content_entity_1 = EntityTest::create(['name' => $this->randomMachineName()]);
    $content_entity_1->save();
    $content_entity_2 = EntityTest::create(['name' => $this->randomMachineName()]);
    $content_entity_2->save();

    return [
      $content_entity_1,
      $content_entity_2,
    ];
  }

}
