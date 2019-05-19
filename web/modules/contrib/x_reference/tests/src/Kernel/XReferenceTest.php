<?php

namespace Drupal\Tests\x_reference\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\x_reference\Entity\XReference;
use Drupal\x_reference\Entity\XReferencedEntity;
use Drupal\x_reference\Entity\XReferenceType;
use Drupal\x_reference\Exception\InvalidXReferenceException;
use Drupal\x_reference\XReferenceHandlerInterface;

/**
 * @group x_reference
 */
class XReferenceTest extends KernelTestBase {

  const X_REFERENCE_TYPE_RED_TO_BLUE = 'red_to_blue';

  const X_REFERENCE_ENTITY_SOURCE_RED = 'source_red';
  const X_REFERENCE_ENTITY_TYPE_RED = 'type_red';
  const X_REFERENCE_ENTITY_SOURCE_BLUE = 'source_blue';
  const X_REFERENCE_ENTITY_TYPE_BLUE = 'type_blue';

  const FIRST_RED_ENTITY_ID = 'first_red_entity';
  const SECOND_RED_ENTITY_ID = 'second_red_entity';

  const BLUE_ENTITY_ID = 'blue_entity';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'x_reference',
  ];

  /** @var XReferenceHandlerInterface */
  protected $XReferenceHandler;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $entity_types = [
      XReferencedEntity::ENTITY_TYPE,
      XReferenceType::ENTITY_TYPE,
      XReference::ENTITY_TYPE,
    ];
    foreach ($entity_types as $entity_type) {
      $this->installEntitySchema($entity_type);
    }

    XReferenceType::create([
      'name' => static::X_REFERENCE_TYPE_RED_TO_BLUE,
      'machine_name' => static::X_REFERENCE_TYPE_RED_TO_BLUE,
      'source_entity_source' => static::X_REFERENCE_ENTITY_SOURCE_RED,
      'source_entity_type' => static::X_REFERENCE_ENTITY_TYPE_RED,
      'target_entity_source' => static::X_REFERENCE_ENTITY_SOURCE_BLUE,
      'target_entity_type' => static::X_REFERENCE_ENTITY_TYPE_BLUE,
    ])->save();

    $this->XReferenceHandler = \Drupal::service('x_reference_handler');
  }

  /**
   * Tests referenced entity auto-creation.
   */
  public function testCreateOrLoad() {
    // Should be created.
    $this->XReferenceHandler->createOrLoadXReferencedEntity(
      static::X_REFERENCE_ENTITY_SOURCE_RED,
      static::X_REFERENCE_ENTITY_TYPE_RED,
      static::FIRST_RED_ENTITY_ID
    );
    // Should be loaded.
    $redEntity = $this->XReferenceHandler->createOrLoadXReferencedEntity(
      static::X_REFERENCE_ENTITY_SOURCE_RED,
      static::X_REFERENCE_ENTITY_TYPE_RED,
      static::FIRST_RED_ENTITY_ID,
      FALSE
    );
    parent::assertNotTrue($redEntity->isNew());
  }

  /**
   * Tests validation constraints on reference creation.
   */
  public function testXReferencedEntityConstraint() {
    $firstRedEntity = $this->XReferenceHandler->createOrLoadXReferencedEntity(
      static::X_REFERENCE_ENTITY_SOURCE_RED,
      static::X_REFERENCE_ENTITY_TYPE_RED,
      static::FIRST_RED_ENTITY_ID
    );
    $secondRedEntity = $this->XReferenceHandler->createOrLoadXReferencedEntity(
      static::X_REFERENCE_ENTITY_SOURCE_RED,
      static::X_REFERENCE_ENTITY_TYPE_RED,
      static::SECOND_RED_ENTITY_ID
    );

    $e = NULL;
    try {
      $this->XReferenceHandler->createOrLoadXReference(static::X_REFERENCE_TYPE_RED_TO_BLUE, $firstRedEntity, $secondRedEntity);
    }
    catch (InvalidXReferenceException $e) {
    }
    static::assertTrue($e);
  }

  /**
   * Tests sources and targets relation.
   */
  public function testXReferenceCreation() {
    $redEntity = $this->XReferenceHandler->createOrLoadXReferencedEntity(
      static::X_REFERENCE_ENTITY_SOURCE_RED,
      static::X_REFERENCE_ENTITY_TYPE_RED,
      static::FIRST_RED_ENTITY_ID
    );
    $blueEntity = $this->XReferenceHandler->createOrLoadXReferencedEntity(
      static::X_REFERENCE_ENTITY_SOURCE_BLUE,
      static::X_REFERENCE_ENTITY_TYPE_BLUE,
      static::BLUE_ENTITY_ID
    );

    $reference = $this->XReferenceHandler->createOrLoadXReference(static::X_REFERENCE_TYPE_RED_TO_BLUE, $redEntity, $blueEntity);
    static::assertNotTrue($reference->isNew());

    $sources = $this->XReferenceHandler->loadSourcesByTarget(static::X_REFERENCE_TYPE_RED_TO_BLUE, $blueEntity);
    static::assertNotEmpty($sources);
    static::assertTrue(isset($sources[$redEntity->id()]));

    $targets = $this->XReferenceHandler->loadTargetsBySource(static::X_REFERENCE_TYPE_RED_TO_BLUE, $redEntity);
    static::assertNotEmpty($targets);
    static::assertTrue(isset($targets[$blueEntity->id()]));
  }

}
