<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel\Views;

use Drupal\views\Views;

/**
 * A test.
 *
 * @group entity_reference_uuid
 */
class EntityReverseNodePlusForwardRef extends UuidViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = [
    'entity_reverse_node_plus_forward_reference',
  ];

  public function testView() {
    // This is a view of test_nodetype_one nodes that have a reference from
    // a test_entity_two that reference to a test_entity_one which have "two"
    // in their name field.
    $view = Views::getView('entity_reverse_node_plus_forward_reference');
    $this->executeView($view);
    $this->assertCount(1, $view->result);
    $this->entities['799cbc6f-b819-47c5-abc6-8bfd430a6574']->set('name', 'Call me two');
    $this->entities['799cbc6f-b819-47c5-abc6-8bfd430a6574']->save();
    $view = Views::getView('entity_reverse_node_plus_forward_reference');
    $this->executeView($view);
    $this->assertCount(2, $view->result);
    foreach ($view->result as $index => $row) {
      $this->assertCount(2, $row->_relationship_entities);
    }
    // Turns out we pull the same node into the view 2x due to the relationship.
    $expected_uuids = [
      'f4924e8b-133b-4d37-b25b-542341850639',
      'f4924e8b-133b-4d37-b25b-542341850639',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    // Change the reference.
    $this->entities['4ccccf2e-805c-421e-b029-bfa79dc7b006']->set('node_one_ref', 'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1');
    $this->entities['4ccccf2e-805c-421e-b029-bfa79dc7b006']->save();
    $view = Views::getView('entity_reverse_node_plus_forward_reference');
    $this->executeView($view);
    $this->assertCount(2, $view->result);
    // Now we pull in two different nodes via the reverse relationship.
    $expected_uuids = [
      'f4924e8b-133b-4d37-b25b-542341850639',
      'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
    ];
    foreach ($view->result as $index => $row) {
      //print($row->_entity->uuid() . "\n");
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    // Update another related test_entity_one to a name that matches the filter.
    $this->entities['4ae62194-1fae-4c3d-b210-ba4b0ad71f7e']->set('name', 'Also call me two');
    $this->entities['4ae62194-1fae-4c3d-b210-ba4b0ad71f7e']->save();
    $view = Views::getView('entity_reverse_node_plus_forward_reference');
    $this->executeView($view);
    $this->assertCount(3, $view->result);
    // Verify the node and related entities that are added to the view result.
    $last = end($view->result);
    $this->assertEquals('cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1', $last->_entity->uuid());
    $this->assertEquals('16b64581-e212-4a1e-a0c7-c471bf914eea', $last->_relationship_entities['reverse__test_entity_two__node_one_ref']->uuid());
    $this->assertEquals('4ae62194-1fae-4c3d-b210-ba4b0ad71f7e', $last->_relationship_entities['entity_one_ref']->uuid());
  }
}
