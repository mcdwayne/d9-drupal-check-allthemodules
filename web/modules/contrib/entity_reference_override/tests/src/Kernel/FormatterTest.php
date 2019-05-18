<?php

namespace Drupal\Tests\entity_reference_override\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\UserCreationTrait;

/**
 * Test entity_reference_override's field formatters.
 *
 * @group entity_reference_override
 */
class FormatterTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference_override',
    'system',
    'entity_test',
    'field',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    FieldStorageConfig::create([
      'field_name' => 'test_ero',
      'entity_type' => 'entity_test',
      'type' => 'entity_reference_override',
      'settings' => [
        'target_type' => 'entity_test',
      ]
    ])->save();
    FieldConfig::create([
      'field_name' => 'test_ero',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();

    \Drupal::currentUser()->setAccount($this->createUser(['view test entity']));
  }

  /**
   * @covers \Drupal\entity_reference_override\Plugin\Field\FieldFormatter\EntityReferenceOverrideLabelFormatter
   *
   * @todo Use other override_actions
   */
  public function testFieldFormatterLabel() {
    $referenced_entity = EntityTest::create([
      'name' => 'referenced entity',
    ]);
    $referenced_entity->save();

    $referencing_entity = EntityTest::create([
      'name' => 'referencing entity',
      'test_ero' => [
        [
          'target_id' => $referenced_entity->id(),
          'override' => 'test override',
        ],
      ]
    ]);
    $referencing_entity->save();

    $build = $referencing_entity->get('test_ero')->view([
      'type' => 'entity_reference_override_label',
      'settings' => [
        'override_action' => 'title',
      ],
    ]);

    $output = $this->render($build);
    $this->assertContains('test override', $output);
  }

  /**
   * @covers \Drupal\entity_reference_override\Plugin\Field\FieldFormatter\EntityReferenceOverrideEntityFormatter
   */
  public function testFieldFormatterView() {
    $referenced_entity = EntityTest::create([
      'name' => 'referenced entity',
    ]);
    $referenced_entity->save();

    $referencing_entity = EntityTest::create([
      'name' => 'referencing entity',
      'test_ero' => [
        [
          'target_id' => $referenced_entity->id(),
          'override' => 'test override',
        ],
      ]
    ]);
    $referencing_entity->save();

    $build = $referencing_entity->get('test_ero')->view([
      'type' => 'entity_reference_override_entity',
      'settings' => [
        'override_action' => 'title',
      ],
    ]);

    $output = $this->render($build);
    $this->assertContains('referenced entity', $output);
  }

  /**
   * Renders the same entity referenced from many different places.
   */
  public function testEntityReferenceRecursiveProtectionWithManyRenderedEntities() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $referenced_entity */
    $referenced_entity = EntityTest::create([
      'name' => 'referenced entity',
    ]);
    $referenced_entity->save();

    $range = range(0, 30);
    $referencing_entities = array_map(function () use ($referenced_entity) {
      $referencing_entity = EntityTest::create([
        'name' => $this->randomMachineName(),
        'test_ero' => [
          [
            'target_id' => $referenced_entity->id(),
            'override' => 'test override',
          ],
        ]
      ]);
      $referencing_entity->save();
      return $referencing_entity;
    }, $range);

    // Set the default view mode to use the 'entity_reference_entity_view'
    // formatter.
    entity_get_display('entity_test', 'entity_test', 'default')
      ->setComponent('test_ero', [
        'type' => 'entity_reference_override_entity',
        'settings' => [
          'override_action' => 'title',
        ],
      ])
      ->save();

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('entity_test');
    $build = $view_builder->viewMultiple($referencing_entities, 'default');
    $output = $this->render($build);

    // The title of entity_test entities is printed twice by default, so we have
    // to multiply the formatter's recursive rendering protection limit by 2.
    // Additionally, we have to take into account 2 additional occurrences of
    // the entity title because we're rendering the full entity, not just the
    // reference field.
    $expected_occurrences = 30 * 2 + 2;
    $actual_occurrences = substr_count($output, $referenced_entity->get('name')->value);
    $this->assertEquals($expected_occurrences, $actual_occurrences);
  }

}
