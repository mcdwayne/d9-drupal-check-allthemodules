<?php

namespace Drupal\Tests\wbm2cm\Unit\Plugin\Deriver;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\wbm2cm\Plugin\Deriver\ModerationDeriver;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\wbm2cm\Plugin\Deriver\ModerationDeriver
 * @group wbm2cm
 */
class ModerationDeriverTest extends UnitTestCase {

  /**
   * The mocked entity type manager service.
   *
   * @var EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
  }

  /**
   * @covers ::getDerivativeDefinitions
   *
   * @dataProvider getDerivativeDefinitionsProvider
   */
  public function testGetDerivativeDefinitions(array $definitions, $expected_count, $expected_derivatives) {
    foreach ($definitions as $entity_type_id => $flags) {
      $definitions[$entity_type_id] = $this->mockEntityType($flags, $entity_type_id);
    }
    $this->entityTypeManager->getDefinitions()->willReturn($definitions);

    $plugin_definition = [
      'id' => $this->randomMachineName(),
      'class' => 'It worked!',
      'source_module' => 'wbm2cm',
    ];

    $deriver = new ModerationDeriver(
      $this->entityTypeManager->reveal(),
      array_keys($definitions)
    );
    $derivatives = $deriver->getDerivativeDefinitions($plugin_definition);

    $this->assertCount($expected_count, $derivatives);
    foreach ($expected_derivatives as $id) {
      $this->assertEquals($plugin_definition, $derivatives[$id]);
    }
  }

  /**
   * Mocks an entity type definition based on flags from the data provider.
   *
   * @param bool[] $flags
   *   Boolean flags that determine the behavior of the mocked entity type. They
   *   represent revisionability and translatability, in that order.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface
   *   The mocked entity type definition.
   */
  protected function mockEntityType(array $flags, $entity_type_id = NULL) {
    list ($revisionable, $translatable) = $flags;

    $entity_type = $this->prophesize(ContentEntityTypeInterface::class);
    $entity_type->id()->willReturn($entity_type_id ?: $this->randomMachineName());
    $entity_type->getProvider()->willReturn('wbm2cm');
    $entity_type->isRevisionable()->willReturn((bool) $revisionable);
    $entity_type->isTranslatable()->willReturn((bool) $translatable);

    return $entity_type->reveal();
  }

  /**
   * Data provider for testGetDerivativeDefinitions().
   *
   * @return array
   *   A set of test scenarios. Each is an array with the following items:
   *   - A set of arrays, each representing an entity type, and keyed by the
   *     entity type ID. Each array is a tuple of three boolean flags
   *     representing (in order) the revisionablity and translatability
   *     of the entity type. See mockEntityType() for more info.
   *   - How many plugin definitions the deriver is expected to return.
   *   - The derivative IDs that the deriver is expected to return.
   */
  public function getDerivativeDefinitionsProvider() {
    return [
      'revisionable and translatable' => [
        [
          'foo' => [TRUE, TRUE],
        ],
        1,
        ['foo'],
      ],
      'translatable but not revisionable' => [
        [
          'foo' => [TRUE, TRUE],
          'bar' => [FALSE, TRUE],
        ],
        1,
        ['foo'],
      ],
      'revisionable but not translatable' => [
        [
          'foo' => [TRUE, TRUE],
          'bar' => [TRUE, FALSE],
        ],
        1,
        ['foo'],
      ],
    ];
  }

}
