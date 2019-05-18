<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel\Views;

use Drupal\views\Views;

/**
 * A test.
 *
 * @group entity_reference_uuid
 */
class EntityForwardNodeRef extends UuidViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = [
    'entity_forward_node_reference',
    'entity_forward_node_reference_unfiltered',
  ];

  public function testView() {
    // This is a view of test_entity_two entities.
    $view = Views::getView('entity_forward_node_reference');
    $this->executeView($view);
    // The fixtures created 4 entities of test_entity_two, and 3 reference
    // a test_nodetype_one.
    $this->assertCount(3, $view->result);
    $expected_uuids = [
      '83109432-2657-4217-bb1a-9bed7ef78599',
      '4ccccf2e-805c-421e-b029-bfa79dc7b006',
      '16b64581-e212-4a1e-a0c7-c471bf914eea',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    $expected_entities = [
      'f4924e8b-133b-4d37-b25b-542341850639',
      'f4924e8b-133b-4d37-b25b-542341850639',
      'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
    ];
    foreach ($view->result as $index => $row) {
      if ($expected_entities[$index]) {
        $this->assertEquals($expected_entities[$index], $row->_relationship_entities['node_one_ref']->uuid());
      }
    }
    // Set the field of the node referenced by 2 entities to a value not allowed
    // by the filter.
    $this->entities['f4924e8b-133b-4d37-b25b-542341850639']->set('field_test_nodetype_one_text', 'llama');
    $this->entities['f4924e8b-133b-4d37-b25b-542341850639']->save();
    $view = Views::getView('entity_forward_node_reference');
    $this->executeView($view);
    $this->assertCount(1, $view->result);
    // This is a view of test_entity_two entities that's not filtered, but
    // the relationship is required.
    $view = Views::getView('entity_forward_node_reference_unfiltered');
    $this->executeView($view);
    // The fixtures created 4 entities of test_entity_two, and 3 reference
    // a test_nodetype_one and one a test_nodetype_two.
    $this->assertCount(4, $view->result);
    $expected_uuids = [
      '208adf04-b0ea-4d8c-b744-e574ec97d1d2',
      '83109432-2657-4217-bb1a-9bed7ef78599',
      '4ccccf2e-805c-421e-b029-bfa79dc7b006',
      '16b64581-e212-4a1e-a0c7-c471bf914eea',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
  }
}
