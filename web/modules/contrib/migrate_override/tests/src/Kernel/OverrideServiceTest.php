<?php

namespace Drupal\Tests\migrate_override\Kernel;

use Drupal\migrate_override\OverrideManagerService;

/**
 * Tests the override manager service.
 *
 * @group migrate_override
 *
 * @coversDefaultClass \Drupal\migrate_override\OverrideManagerService
 */
class OverrideServiceTest extends MigrateOverrideTestBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->entityFieldManager = $this->container->get('entity_field.manager');
  }

  /**
   * Tests creating bundle fields.
   *
   * @covers ::createBundleField
   * @covers ::createFieldStorage
   * @covers ::entityBundleHasField
   * @covers ::entityHasFieldStorage
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCreateBundleField() {
    $this->createContentType('test_type_1');
    $this->createContentType('test_type_2');

    $this->assertFalse($this->overrideManager->entityBundleHasField('node', 'test_type_1'));
    $this->assertFalse($this->overrideManager->entityBundleHasField('node', 'test_type_2'));
    $entity_fields = $this->entityFieldManager->getFieldStorageDefinitions('node');
    $this->assertArrayNotHasKey(OverrideManagerService::FIELD_NAME, $entity_fields);

    $this->overrideManager->createBundleField('node', 'test_type_1');
    $this->assertTrue($this->overrideManager->entityBundleHasField('node', 'test_type_1'));
    $this->assertFalse($this->overrideManager->entityBundleHasField('node', 'test_type_2'));
    $entity_fields = $this->entityFieldManager->getFieldStorageDefinitions('node');
    $bundle1_fields = $this->entityFieldManager->getFieldDefinitions('node', 'test_type_1');
    $bundle2_fields = $this->entityFieldManager->getFieldDefinitions('node', 'test_type_2');
    $this->assertArrayHasKey(OverrideManagerService::FIELD_NAME, $entity_fields);
    $this->assertArrayHasKey(OverrideManagerService::FIELD_NAME, $bundle1_fields);
    $this->assertArrayNotHasKey(OverrideManagerService::FIELD_NAME, $bundle2_fields);

    $this->overrideManager->createBundleField('node', 'test_type_2');
    $this->assertTrue($this->overrideManager->entityBundleHasField('node', 'test_type_1'));
    $this->assertTrue($this->overrideManager->entityBundleHasField('node', 'test_type_2'));
    $entity_fields = $this->entityFieldManager->getFieldStorageDefinitions('node');
    $bundle1_fields = $this->entityFieldManager->getFieldDefinitions('node', 'test_type_1');
    $bundle2_fields = $this->entityFieldManager->getFieldDefinitions('node', 'test_type_2');
    $this->assertArrayHasKey(OverrideManagerService::FIELD_NAME, $entity_fields);
    $this->assertArrayHasKey(OverrideManagerService::FIELD_NAME, $bundle1_fields);
    $this->assertArrayHasKey(OverrideManagerService::FIELD_NAME, $bundle2_fields);
  }

  /**
   * Tests deleting bundle fields.
   *
   * @covers ::deleteBundleField
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testDeleteBundleField() {
    $this->createContentType('test_type_1');
    $this->createContentType('test_type_2');

    $this->overrideManager->createBundleField('node', 'test_type_1');
    $this->overrideManager->createBundleField('node', 'test_type_2');
    $this->assertTrue($this->overrideManager->entityBundleHasField('node', 'test_type_1'));
    $this->assertTrue($this->overrideManager->entityBundleHasField('node', 'test_type_2'));

    $this->overrideManager->deleteBundleField('node', 'test_type_2');
    $this->assertTrue($this->overrideManager->entityBundleHasField('node', 'test_type_1'));
    $this->assertFalse($this->overrideManager->entityBundleHasField('node', 'test_type_2'));
    $entity_fields = $this->entityFieldManager->getFieldStorageDefinitions('node');
    $bundle1_fields = $this->entityFieldManager->getFieldDefinitions('node', 'test_type_1');
    $bundle2_fields = $this->entityFieldManager->getFieldDefinitions('node', 'test_type_2');
    $this->assertArrayHasKey(OverrideManagerService::FIELD_NAME, $entity_fields);
    $this->assertArrayHasKey(OverrideManagerService::FIELD_NAME, $bundle1_fields);
    $this->assertArrayNotHasKey(OverrideManagerService::FIELD_NAME, $bundle2_fields);

    $this->overrideManager->deleteBundleField('node', 'test_type_1');
    $this->assertFalse($this->overrideManager->entityBundleHasField('node', 'test_type_1'));
    $this->assertFalse($this->overrideManager->entityBundleHasField('node', 'test_type_2'));
    $entity_fields = $this->entityFieldManager->getFieldStorageDefinitions('node');
    $this->assertArrayNotHasKey(OverrideManagerService::FIELD_NAME, $entity_fields);

  }

  /**
   * Tests getEntityFieldStatus.
   *
   * @covers ::getEntityFieldStatus
   */
  public function testGetEntityFieldStatus() {
    $this->createContentType();
    $this->addTextField('field_test');
    $this->overrideManager->createBundleField('node', 'test_type');
    $node = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'title' => 'test node',
      'type' => 'test_type',
      'body' => ['value' => 'test body'],
      'field_text' => "Text field",
    ]);
    $node->save();

    $this->assertTrue($node->get(OverrideManagerService::FIELD_NAME)->isEmpty());
    $this->assertSame(OverrideManagerService::ENTITY_FIELD_LOCKED, $this->overrideManager->getEntityFieldStatus($node, 'field_text'));
    $override = ['field_text' => OverrideManagerService::ENTITY_FIELD_OVERRIDDEN];
    $node->migrate_override_data = [['value' => serialize($override)]];
    $node->save();

    $this->assertSame(OverrideManagerService::ENTITY_FIELD_OVERRIDDEN, $this->overrideManager->getEntityFieldStatus($node, 'field_text'));

  }

  /**
   * Tests setEntityFieldStatus.
   *
   * @covers ::setEntityFieldStatus
   */
  public function testSetEntityFieldStatus() {
    $this->createContentType();
    $this->addTextField('field_test');
    $this->overrideManager->createBundleField('node', 'test_type');
    $node = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'title' => 'test node',
      'type' => 'test_type',
      'body' => ['value' => 'test body'],
      'field_text' => "Text field",
    ]);
    $node->save();

    $this->assertTrue($node->get(OverrideManagerService::FIELD_NAME)->isEmpty());
    $this->assertSame(OverrideManagerService::ENTITY_FIELD_LOCKED, $this->overrideManager->getEntityFieldStatus($node, 'field_text'));
    $this->overrideManager->setEntityFieldStatus($node, 'field_text', OverrideManagerService::ENTITY_FIELD_OVERRIDDEN);
    $node->save();

    $override = serialize(['field_text' => OverrideManagerService::ENTITY_FIELD_OVERRIDDEN]);
    $this->assertFalse($node->get(OverrideManagerService::FIELD_NAME)->isEmpty());
    $this->assertSame($override, $node->get(OverrideManagerService::FIELD_NAME)->value);

    $this->assertSame(OverrideManagerService::ENTITY_FIELD_OVERRIDDEN, $this->overrideManager->getEntityFieldStatus($node, 'field_text'));
  }

}
