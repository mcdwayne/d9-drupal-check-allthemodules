<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel\Views;

use Drupal\views\Views;

/**
 * A test.
 *
 * @group entity_reference_uuid
 */
class NodeForwardEntityRef extends UuidViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = [
    'node_forward_entity_reference',
  ];

  public function testView() {
    $view = Views::getView('node_forward_entity_reference');
    $this->executeView($view);
    // The fixtures created 3 nodes of test_nodetype_two, and all 3 reference
    // a test_nodetype_one. The 3rd test_nodetype_one will be excluded by the
    // filter.
    $this->assertCount(2, $view->result);
    $expected_uuids = [
      '1c73e274-7077-45dc-943a-655259d2ae6f',
      '567a1ae4-e542-459c-aef1-976aa66b15b5',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    // Add another with a reference to a test_entity_one with disallowed filter
    // value.
    $test_nodetype_two = [
      [
        'uuid' => '48390cfc-6a3e-411e-8ca0-f88d0d36481e',
        'title' => 'Mister three',
        'field_entity_one_ref' => '4ae62194-1fae-4c3d-b210-ba4b0ad71f7e',
      ],
    ];
    $this->createNodes('test_nodetype_two', $test_nodetype_two);
    $view = Views::getView('node_forward_entity_reference');
    $this->executeView($view);
    $this->assertCount(2, $view->result);
    // Add another with a reference to a test_entity_one with allowed filter
    // value.

    $test_nodetype_two = [
      [
        'uuid' => 'f0637aab-4969-4b89-9405-9d70e75f4e05',
        'title' => 'Mister three',
        'field_entity_one_ref' => '799cbc6f-b819-47c5-abc6-8bfd430a6574',
      ],
    ];
    $this->createNodes('test_nodetype_two', $test_nodetype_two);
    $view = Views::getView('node_forward_entity_reference');
    $this->executeView($view);
    $this->assertCount(3, $view->result);
    $expected_uuids = [
      '1c73e274-7077-45dc-943a-655259d2ae6f',
      '567a1ae4-e542-459c-aef1-976aa66b15b5',
      'f0637aab-4969-4b89-9405-9d70e75f4e05'
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
  }
}
