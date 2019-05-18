<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides tests for EntityReferenceUuidItem.
 *
 * @group entity_reference_uuid
 *
 * @see \Drupal\KernelTests\Core\Entity\EntityReferenceFieldTest
 */
class EntityReferenceUuidItemTest extends EntityKernelTestBase {

  use SchemaCheckTestTrait;
  use EntityReferenceUuidTestTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The entity type that is being referenced.
   *
   * @var string
   */
  protected $referencedEntityType = 'entity_test_rev';

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
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_uuid',
    'entity_reference_test',
    'entity_test_update',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');

    // Create a field.
    $this->createEntityReferenceUuidField(
      $this->entityType,
      $this->bundle,
      $this->fieldName,
      'Field test',
      $this->referencedEntityType,
      'default',
      ['target_bundles' => [$this->bundle]],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests reference field validation.
   */
  public function testEntityReferenceFieldValidation() {
    $real = 'b82d8a89-4de8-4500-8fa2-e9c347dc378';
    $fake = 'b82d8a89-4de8-4500-8fa2-e9c347dc379';

    // Test a valid reference.
    $storage = $this->entityTypeManager
      ->getStorage($this->referencedEntityType);
    $referenced_entity = $storage
      ->create(['type' => $this->bundle, 'uuid' => $real]);
    $referenced_entity->save();
    $uuid = $referenced_entity->uuid();
    $this->assertEquals($real, $uuid);

    $entity = $this->entityTypeManager
      ->getStorage($this->entityType)
      ->create(['type' => $this->bundle]);
    /** @var \Drupal\entity_reference_uuid\Plugin\Field\FieldType\EntityReferenceUuidItem $field */
    $field = $entity->{$this->fieldName};
    $field->target_id = $referenced_entity->id();
    /** @var \Drupal\Core\Entity\EntityConstraintViolationList $violations */
    $violations = $field->validate();
    $this->assertEquals(0, $violations->count(), 'Validation passes.');
    /** @var \Drupal\Core\Entity\Plugin\DataType\EntityReference $ref */
    $ref = $field[0]->get('entity');
    /** @var \Drupal\Core\Entity\Entity $ent */
    $ent = $ref->getParent()->entity;
    $this->assertEquals($uuid, $ent->uuid());

    // Test an invalid reference.
    $field->target_id = $fake;
    /** @var \Drupal\Core\Entity\EntityConstraintViolationList $violations */
    $violations = $field->validate();
    $this->assertEquals(2, $violations->count(), 'Validation throws a violation.');
    $this->assertEquals(t('The referenced entity (%type: %id) does not exist.', ['%type' => $this->referencedEntityType, '%id' => $fake]), $violations[0]->getMessage());
    $this->assertEquals(t('The referenced entity (%type: %id) does not exist.', ['%type' => $this->referencedEntityType, '%id' => $fake]), $violations[1]->getMessage());

    // Test a non-referenceable bundle.
    entity_test_create_bundle('non_referenceable', NULL, $this->referencedEntityType);
    $referenced_entity = $storage->create(['type' => 'non_referenceable']);
    $referenced_entity->save();
    $field->target_uuid = $referenced_entity->uuid();
    $violations = $field->validate();
    $this->assertEquals(2, $violations->count(), 'Validation throws a violation.');
    $this->assertEquals(t('This entity (%type: %id) cannot be referenced.', ['%type' => $this->referencedEntityType, '%id' => $referenced_entity->id()]), $violations[0]->getMessage());
    $this->assertEquals(t('This entity (%type: %id) cannot be referenced.', ['%type' => $this->referencedEntityType, '%id' => $referenced_entity->id()]), $violations[1]->getMessage());
  }

}
