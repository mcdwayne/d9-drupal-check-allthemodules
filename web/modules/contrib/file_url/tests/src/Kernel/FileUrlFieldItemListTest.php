<?php

namespace Drupal\Tests\file_url\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file_url\Entity\RemoteFile;
use Drupal\file_url\Plugin\Field\FieldType\FileUrlFieldItemList;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the overridden field item list.
 *
 * @group file_url
 * @coversDefaultClass \Drupal\file_url\Plugin\Field\FieldType\FileUrlFieldItemList
 */
class FileUrlFieldItemListTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file', 'file_url'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A local test file entity.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $localFile;

  /**
   * A remote test file entity.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $remoteFile;

  /**
   * Directory where the sample files are stored.
   *
   * @var string
   */
  protected $directory;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    FieldStorageConfig::create([
      'field_name' => 'file_url_test',
      'entity_type' => 'entity_test',
      'type' => 'file_url',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    $this->directory = $this->getRandomGenerator()->name(8);
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'file_url_test',
      'bundle' => 'entity_test',
      'settings' => ['file_directory' => $this->directory],
    ])->save();

    // Create a local test file.
    file_put_contents('public://example.txt', $this->randomMachineName());
    $this->localFile = File::create(['uri' => 'public://example.txt']);
    $this->localFile->save();

    // Create a remote test file.
    $this->remoteFile = RemoteFile::create(['uri' => $this->getRandomUrl()]);
    $this->remoteFile->save();
  }

  /**
   * Tests the retrieval of referenced files.
   *
   * @covers ::referencedEntities
   */
  public function testReferencedEntities() {
    // Create a test entity.
    $entity = EntityTest::create();

    // Check that the test file field has our overridden field item list.
    if (!$entity->file_url_test instanceof FileUrlFieldItemList) {
      $this->fail('A freshly created File Url field has the correct item list class.');
    }
    // Populate the file field with references to both a local and a remote
    // file.
    $entity->file_url_test->setValue([
      0 => ['target_id' => $this->localFile->id()],
      1 => ['target_id' => $this->remoteFile->id()],
    ]);
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $reloaded_entity = $this->reloadEntity($entity);

    // Check that the file field still has our overridden field item list after
    // loading it from the database.
    if (!$entity->file_url_test instanceof FileUrlFieldItemList) {
      $this->fail('A freshly created File Url field has the correct item list class.');
    }

    // Check that both the local and the remote file references are returned.
    $referenced_entities = $reloaded_entity->file_url_test->referencedEntities();

    $this->assertTrue(!empty($referenced_entities[0]));
    $this->assertEquals('Drupal\file\Entity\File', get_class($referenced_entities[0]));
    $this->assertEquals($this->localFile->id(), $referenced_entities[0]->id());

    $this->assertTrue(!empty($referenced_entities[1]));
    $this->assertEquals('Drupal\file_url\Entity\RemoteFile', get_class($referenced_entities[1]));
    $this->assertEquals($this->remoteFile->id(), $referenced_entities[1]->id());
  }

  /**
   * Returns a random URL.
   *
   * @return string
   *   The random URL.
   */
  protected function getRandomUrl() {
    return 'http://example.com/' . $this->randomMachineName();
  }

  /**
   * Reloads the given entity from the storage and returns it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be reloaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity) {
    $controller = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $controller->resetCache([$entity->id()]);
    return $controller->load($entity->id());
  }

}
