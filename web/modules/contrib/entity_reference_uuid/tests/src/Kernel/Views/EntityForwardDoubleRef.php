<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel\Views;

use Drupal\views\Views;

/**
 * A test.
 *
 * @group entity_reference_uuid
 */
class EntityForwardDoubleRef extends UuidViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = [
    'entity_forward_double_reference',
  ];

  public function testView() {
    // This is a view of test_entity_two entities that relate to a
    // test_entity_one that is published and to a node that has field
    // field_test_nodetype_one_text with values one, two, or three and is
    // published.
    $view = Views::getView('entity_forward_double_reference');
    $this->executeView($view);
    $this->assertCount(2, $view->result);
    foreach ($view->result as $index => $row) {
      $this->assertCount(2, $row->_relationship_entities);
    }
    $expected_uuids = [
      '83109432-2657-4217-bb1a-9bed7ef78599',
      '4ccccf2e-805c-421e-b029-bfa79dc7b006',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    // Publish a related test_entity_one.
    $this->entities['4ae62194-1fae-4c3d-b210-ba4b0ad71f7e']->setPublished();
    $this->entities['4ae62194-1fae-4c3d-b210-ba4b0ad71f7e']->save();
    $view = Views::getView('entity_forward_double_reference');
    $this->executeView($view);
    $this->assertCount(3, $view->result);
    $expected_uuids = [
      '83109432-2657-4217-bb1a-9bed7ef78599',
      '4ccccf2e-805c-421e-b029-bfa79dc7b006',
      '16b64581-e212-4a1e-a0c7-c471bf914eea',
    ];
    $expected_entities = [
      [
        'node_one_ref' => 'f4924e8b-133b-4d37-b25b-542341850639',
        'entity_one_ref' => '65170b9b-2b3c-416b-8bce-3d843bff890c',
      ],
      [
        'node_one_ref' => 'f4924e8b-133b-4d37-b25b-542341850639',
        'entity_one_ref' => '799cbc6f-b819-47c5-abc6-8bfd430a6574',
      ],
      [
        'node_one_ref' => 'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
        'entity_one_ref' => '4ae62194-1fae-4c3d-b210-ba4b0ad71f7e',
      ],
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
      foreach ($expected_entities[$index] as $key => $uuid) {
        $this->assertEquals($uuid, $row->_relationship_entities[$key]->uuid());
      }
    }
    // Add another entity to be found.
    $test_entity_two = [
      [
        'name' => 'Mister two five',
        'uuid' => 'fa0f6150-8814-470d-b861-991f6788e186',
        'entity_one_ref' => 'a6b05258-4381-4b15-83eb-f2b2edc3f1f3',
        // References a test_nodetype_two.
        'node_one_ref' => 'dc2edcbe-8fca-4b26-9850-c5fc77c0c1e0',
      ],
    ];
    $this->createEntities('test_entity_two', $test_entity_two);
    $view = Views::getView('entity_forward_double_reference');
    $this->executeView($view);
    $this->assertCount(4, $view->result);
    $expected_uuids[] = 'fa0f6150-8814-470d-b861-991f6788e186';
    $expected_entities[] = [
      'node_one_ref' => 'dc2edcbe-8fca-4b26-9850-c5fc77c0c1e0',
      'entity_one_ref' => 'a6b05258-4381-4b15-83eb-f2b2edc3f1f3',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
      foreach ($expected_entities[$index] as $key => $uuid) {
        $this->assertEquals($uuid, $row->_relationship_entities[$key]->uuid());
      }
    }
  }
}
