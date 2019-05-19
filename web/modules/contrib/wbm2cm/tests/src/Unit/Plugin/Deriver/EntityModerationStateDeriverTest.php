<?php

namespace Drupal\Tests\wbm2cm\Unit\Plugin\Deriver;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\wbm2cm\Plugin\Deriver\EntityModerationStateDeriver;
use Drupal\Tests\UnitTestCase;
use Drupal\workbench_moderation\ModerationInformationInterface;

/**
 * @coversDefaultClass \Drupal\wbm2cm\Plugin\Deriver\EntityModerationStateDeriver
 * @group wbm2cm
 */
class EntityModerationStateDeriverTest extends UnitTestCase {

  /**
   * The mocked entity type manager service.
   *
   * @var EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The mocked moderation information service.
   *
   * @var ModerationInformationInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $moderationInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->moderationInfo = $this->prophesize(ModerationInformationInterface::class);
  }

  /**
   * @covers ::getDerivativeDefinitions
   *
   * @dataProvider getDerivativeDefinitionsProvider
   */
  public function testGetDerivativeDefinitions(array $definitions, $expected_count, $expected_derivatives) {
    $definitions = array_map([$this, 'mockEntityType'], $definitions);
    $this->entityTypeManager->getDefinitions()->willReturn($definitions);

    $plugin_definition = [
      'class' => 'It worked!',
    ];

    $deriver = new EntityModerationStateDeriver(
      $this->entityTypeManager->reveal(),
      $this->moderationInfo->reveal()
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
   *   Boolean flags that determine the behavior of the mocked entity type. In
   *   order, they represent revisionability, translatability, and
   *   moderatability.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The mocked entity type definition.
   */
  protected function mockEntityType(array $flags) {
    list ($revisionable, $translatable, $moderatable) = $flags;

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->isRevisionable()->willReturn((bool) $revisionable);
    $entity_type->isTranslatable()->willReturn((bool) $translatable);

    $entity_type = $entity_type->reveal();

    $this->moderationInfo
      ->isModeratableEntityType($entity_type)
      ->willReturn((bool) $moderatable);

    return $entity_type;
  }

  /**
   * Data provider for testGetDerivativeDefinitions().
   *
   * @return array
   *   A set of test scenarios. Each is an array with the following items:
   *   - A set of arrays, each representing an entity type, and keyed by the
   *     entity type ID. Each array is a tuple of three boolean flags
   *     representing (in order) the revisionablity, translatability, and
   *     moderatability of the entity type. See mockEntityType() for more.
   *   - How many plugin definitions the deriver is expected to return.
   *   - The derivative IDs that the deriver is expected to return.
   */
  public function getDerivativeDefinitionsProvider() {
    return [
      'compatible' => [
        [
          'foo' => [TRUE, TRUE, TRUE],
        ],
        1,
        ['foo'],
      ],
      'translatable, moderatable, but not revisionable' => [
        [
          'foo' => [TRUE, TRUE, TRUE],
          'bar' => [FALSE, TRUE, TRUE],
        ],
        1,
        ['foo'],
      ],
      'revisionable, moderatable, but not translatable' => [
        [
          'foo' => [TRUE, TRUE, TRUE],
          'bar' => [TRUE, FALSE, TRUE],
        ],
        1,
        ['foo'],
      ],
      'revisionable, translatable, but not moderatable' => [
        [
          'foo' => [TRUE, TRUE, TRUE],
          'bar' => [TRUE, TRUE, FALSE],
        ],
        1,
        ['foo'],
      ],
    ];
  }

}
