<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel\Views;

use Drupal\views\Views;

/**
 * A test.
 *
 * @group entity_reference_uuid
 */
class NodeForwardRef extends UuidViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = [
    'node_forward_reference',
    'node_forward_reference_unfiltered',
  ];

  public function testView() {
    $view = Views::getView('node_forward_reference');
    $this->executeView($view);
    // The fixtures created 3 nodes of test_nodetype_two, and all 3 reference
    // a test_nodetype_one. The 3rd test_nodetype_one will be excluded by the
    // filter.
    $this->assertCount(2, $view->result);
    $expected_uuids = [
      '8a4c2dbe-e87d-4edd-a58f-40cb00092ecb',
      '3999aafc-8b3c-4005-9bf8-554cbfb0df22',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    // Add another with a reference to a test_nodetype_one with allowed filter
    // value.
    $test_nodetype_two = [
      [
        'uuid' => '1e6561c6-5078-4502-95ca-21b8f17d772a',
        'title' => 'Mister three',
        'field_node_one_ref' => 'f4924e8b-133b-4d37-b25b-542341850639',
      ],
    ];
    $this->createNodes('test_nodetype_two', $test_nodetype_two);
    $view = Views::getView('node_forward_reference');
    $this->executeView($view);
    $this->assertCount(3, $view->result);
    $expected_uuids = [
      '8a4c2dbe-e87d-4edd-a58f-40cb00092ecb',
      '3999aafc-8b3c-4005-9bf8-554cbfb0df22',
      '1e6561c6-5078-4502-95ca-21b8f17d772a',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    // Check the view without filtering on the related node's field. We should
    // also see the node with no reference since the relationship is not
    // required.
    $view = Views::getView('node_forward_reference_unfiltered');
    $this->executeView($view);
    $this->assertCount(6, $view->result);
    $expected_uuids = [
      '8a4c2dbe-e87d-4edd-a58f-40cb00092ecb',
      '3999aafc-8b3c-4005-9bf8-554cbfb0df22',
      '1c73e274-7077-45dc-943a-655259d2ae6f',
      'dffc353f-cde2-4d7b-98ad-82f157ffdd72',
      '567a1ae4-e542-459c-aef1-976aa66b15b5',
      '1e6561c6-5078-4502-95ca-21b8f17d772a',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
  }
}
