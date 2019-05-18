<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel\Views;

use Drupal\views\Views;

/**
 * A test.
 *
 * @group entity_reference_uuid
 */
class EntityReverseNodeRef extends UuidViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = [
    'entity_reverse_node_reference',
  ];

  public function testView() {
    // This is a view of test_nodetype_one nodes that related from a
    // test_entity_two where both the node and entity are published.
    $view = Views::getView('entity_reverse_node_reference');
    $this->executeView($view);
    $this->assertCount(2, $view->result);
    // Publish another related test_entity_two.
    $this->entities['4ccccf2e-805c-421e-b029-bfa79dc7b006']->setPublished();
    $this->entities['4ccccf2e-805c-421e-b029-bfa79dc7b006']->save();
    $view = Views::getView('entity_reverse_node_reference');
    $this->executeView($view);
    $this->assertCount(3, $view->result);
    $expected_uuids = [
      'f4924e8b-133b-4d37-b25b-542341850639',
      'f4924e8b-133b-4d37-b25b-542341850639',
      'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    $test_entity_two = [
      [
        'name' => 'Mister two five',
        'uuid' => '961034ac-c4a6-4c07-b1f2-5659ae0c275b',
        'entity_one_ref' => '799cbc6f-b819-47c5-abc6-8bfd430a6574',
        // References a test_nodetype_one.
        'node_one_ref' => '40894f20-922f-4564-ad68-19b67d4520f5',
      ],
    ];
    $this->createEntities('test_entity_two', $test_entity_two);
    $view = Views::getView('entity_reverse_node_reference');
    $this->executeView($view);
    $this->assertCount(4, $view->result);
    $last = end($view->result);
    $this->assertEquals('40894f20-922f-4564-ad68-19b67d4520f5', $last->_entity->uuid());
  }
}
