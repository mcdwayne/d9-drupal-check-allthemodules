<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel\Views;

use Drupal\views\Views;

/**
 * A test.
 *
 * @group entity_reference_uuid
 */
class NodeReverseEntityRef extends UuidViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = [
    'node_reverse_entity_reference',
    'node_reverse_entity_reference_unfiltered',
  ];

  public function testView() {
    $view = Views::getView('node_reverse_entity_reference');
    $this->executeView($view);
    // This is a view of published nodes of type test_nodetype_one that have a
    // reference from a test_entity_two that is published.
    $this->assertCount(2, $view->result);
    $expected_uuids = [
      'f4924e8b-133b-4d37-b25b-542341850639',
      'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    // Create another node.
    $test_nodetype_one = [
      [
        'uuid' => '0665beb4-ef53-435d-82b7-e2757ab85670',
        'title' => 'Dummy one five',
        'field_test_nodetype_one_text' => 'dog',
      ]
    ];
    $this->createNodes('test_nodetype_one', $test_nodetype_one);
    $view = Views::getView('node_reverse_entity_reference');
    $this->executeView($view);
    // The new node is not referenced, so the count is unchanged.
    $this->assertCount(2, $view->result);
    // Create an entity referencing it, so it's now in the view result.
    $test_entity_two = [
      [
        'name' => 'Mister two five',
        'uuid' => '2cb7adc2-f471-42d2-aa7b-28e87747d499',
        'entity_one_ref' => '4ae62194-1fae-4c3d-b210-ba4b0ad71f7e',
        // References a test_nodetype_one.
        'node_one_ref' => '0665beb4-ef53-435d-82b7-e2757ab85670',
      ],
    ];
    $this->createEntities('test_entity_two', $test_entity_two);
    $view = Views::getView('node_reverse_entity_reference');
    $this->executeView($view);
    // The new node is not referenced, so the count is unchanged.
    $this->assertCount(3, $view->result);
    $expected_uuids = [
      'f4924e8b-133b-4d37-b25b-542341850639',
      'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
      '0665beb4-ef53-435d-82b7-e2757ab85670',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    // Unpublish the new test_entity_two and verify the referenced node is not
    // in the result.
    $this->entities['2cb7adc2-f471-42d2-aa7b-28e87747d499']->setUnpublished();
    $this->entities['2cb7adc2-f471-42d2-aa7b-28e87747d499']->save();
    $view = Views::getView('node_reverse_entity_reference');
    $this->executeView($view);
    $this->assertCount(2, $view->result);

    // This is a view of any nodes of type test_nodetype_one.
    $view = Views::getView('node_reverse_entity_reference_unfiltered');
    $this->executeView($view);
    $this->assertCount(6, $view->result);
    // Two entities reference the same node, so it appears in the result 2x.
    $expected_uuids = [
      'f4924e8b-133b-4d37-b25b-542341850639',
      'f4924e8b-133b-4d37-b25b-542341850639',
      'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
      'dc2edcbe-8fca-4b26-9850-c5fc77c0c1e0',
      '40894f20-922f-4564-ad68-19b67d4520f5',
      '0665beb4-ef53-435d-82b7-e2757ab85670',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    $expected_entities = [
      '83109432-2657-4217-bb1a-9bed7ef78599',
      '4ccccf2e-805c-421e-b029-bfa79dc7b006',
      '16b64581-e212-4a1e-a0c7-c471bf914eea',
      FALSE,
      FALSE,
      '2cb7adc2-f471-42d2-aa7b-28e87747d499',
    ];
    foreach ($view->result as $index => $row) {
      if ($expected_entities[$index]) {
        $this->assertEquals($expected_entities[$index], $row->_relationship_entities['reverse__test_entity_two__node_one_ref']->uuid());
      }
    }
  }
}
