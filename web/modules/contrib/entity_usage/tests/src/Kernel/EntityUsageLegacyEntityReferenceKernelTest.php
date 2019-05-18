<?php

namespace Drupal\Tests\entity_usage\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;

/**
 * Tests basic usage tracking on generic entities.
 *
 * THIS IS A LEGACY TEST AND SHOULD NOT BE FURTHER IMPROVED OR EXTENDED.
 *
 * @group entity_usage
 *
 * @package Drupal\Tests\entity_usage\Kernel
 *
 */
class EntityUsageLegacyEntityReferenceKernelTest extends EntityKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['entity_reference_test', 'entity_usage'];

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
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * The entity to be referenced in this test.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $referencedEntity;

  /**
   * Some test entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $testEntities;

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
    $this->tableName = 'entity_usage';
    $this->createEntityReferenceField($this->entityType, $this->bundle, $this->fieldName, 'Field test', $this->entityType, 'default', [], FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Set up an additional field.
    FieldStorageConfig::create([
      'field_name' => 'body',
      'entity_type' => $this->entityType,
      'type' => 'text',
      'settings' => [],
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'field_name' => 'body',
      'label' => 'Body',
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
    $this->testEntities = $this->getTestEntities();
    $this->testEntities[0]->body = [
      'value' => '<p>Lorem ipsum 1</p>',
      'format' => 'full_html',
    ];
    $this->testEntities[0]->save();
    $this->testEntities[1]->body = [
      'value' => '<p>Lorem ipsum 2</p>',
      'format' => 'full_html',
    ];
    $this->testEntities[1]->save();

  }

  /**
   * Tests basic entity tracking on test entities using entityreference fields.
   */
  public function testEntityReferenceTracking() {

    // @todo Refactor this test with the new API methods.

    /** @var \Drupal\entity_usage\EntityUsage $entity_usage */
    $entity_usage = $this->container->get('entity_usage.usage');

    $field_name = $this->fieldName;
    /** @var \Drupal\node\NodeInterface $source_entity */
    $source_entity = $this->testEntities[0];

    // First check usage is 0 for the referenced entity.
    $usage = $entity_usage->listSources($this->referencedEntity);
    $this->assertSame([], $usage, 'Initial usage is correctly empty.');

    // Reference from other entity and check that the usage increases to 1.
    $source_entity->{$field_name}->entity = $this->referencedEntity;
    $source_entity->save();
    $usage = $entity_usage->listSources($this->referencedEntity);
    $this->assertEquals([
      $source_entity->getEntityTypeId() => [
        $source_entity->id() => [
          [
            'source_langcode' => $source_entity->language()->getId(),
            'source_vid' => $source_entity->getRevisionId() ?: 0,
            'method' => 'entity_reference',
            'field_name' => $field_name,
            'count' => 1,
          ],
        ],
      ],
    ], $usage, 'The usage count is correct.');

    // Update other values on the source entity, check usage remains 1.
    $source_entity->body = [
      'value' => '<p>Modified lorem ipsum</p>',
      'format' => 'full_html',
    ];
    $source_entity->save();
    $usage = $entity_usage->listSources($this->referencedEntity);
    $this->assertEquals([
      $source_entity->getEntityTypeId() => [
        $source_entity->id() => [
          0 => [
            'source_langcode' => $source_entity->language()->getId(),
            'source_vid' => $source_entity->getRevisionId() ?: 0,
            'method' => 'entity_reference',
            'field_name' => $field_name,
            'count' => 1,
          ],
        ],
      ],
    ], $usage, 'The usage count is correct.');

    // Delete the field value from the entity reference field and check that the
    // usage goes back to 0.
//    $source_entity->{$field_name}->entity = $this->testEntities[1];
//    $source_entity->save();
//    $usage = $entity_usage->listSources($this->referencedEntity);
//    $this->assertSame([], $usage, 'Non-referenced usage is correctly empty.');

    // Create a reference again, check the value is back to 1.
    $source_entity->{$field_name}->entity = $this->referencedEntity;
    $source_entity->save();
    $usage = $entity_usage->listSources($this->referencedEntity);
    $this->assertEquals([
      $source_entity->getEntityTypeId() => [
        $source_entity->id() => [
          0 => [
            'source_langcode' => $source_entity->language()->getId(),
            'source_vid' => $source_entity->getRevisionId() ?: 0,
            'method' => 'entity_reference',
            'field_name' => $field_name,
            'count' => 1,
          ],
        ],
      ],
    ], $usage, 'The usage count is correct.');

    // Delete the whole source entity, check usage goes back to 0.
//    $source_entity->delete();
//    $usage = $entity_usage->listSources($this->referencedEntity);
//    $this->assertSame([], $usage, 'Non-referenced usage is correctly empty.');

    // Create a reference again, check the value is back to 1.
//    $source_entity = $this->testEntities[1];
//    $source_entity->{$field_name}->entity = $this->referencedEntity;
//    $source_entity->save();
//    $usage = $entity_usage->listSources($this->referencedEntity);
//    $this->assertEquals([
//      $source_entity->getEntityTypeId() => [
//        $source_entity->id() => [
//          0 => [
//            'source_langcode' => $source_entity->language()->getId(),
//            'source_vid' => $source_entity->getRevisionId() ?: 0,
//            'method' => 'entity_reference',
//            'field_name' => $field_name,
//            'count' => 1,
//          ],
//        ],
//      ],
//    ], $usage, 'The usage count is correct.');

    // Unpublish the source entity, check usage goes back to 0.
    // We don't deal with entities statuses yet.
    /*
    $source_entity->status = FALSE;
    $source_entity->save();
    $usage = $entity_usage->listSources($this->referencedEntity);
    $this->assertSame([], $usage, 'Non-referenced usage is correctly empty.');
     */
  }

  /**
   * Creates two test entities.
   *
   * @return array
   *   An array of entity objects.
   */
  protected function getTestEntities() {
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
