<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel\Views;

use Drupal\views\Views;

/**
 * A test.
 *
 * @group entity_reference_uuid
 */
class NodeReverseRef extends UuidViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = [
    'node_reverse_reference',
  ];

  public function testView() {
    $view = Views::getView('node_reverse_reference');
    $this->executeView($view);
    // The fixtures created 4 nodes of test_nodetype_one, and 3 are referenced
    // by a test_nodetype_two. The relationship is required.
    $this->assertCount(3, $view->result);
    $expected_uuids = [
      'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
      'dc2edcbe-8fca-4b26-9850-c5fc77c0c1e0',
      '40894f20-922f-4564-ad68-19b67d4520f5',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    // Add another with a reference to a test_nodetype_one but unpublished
    // so excluded by the filter.
    $test_nodetype_two = [
      [
        'uuid' => '1e6561c6-5078-4502-95ca-21b8f17d772a',
        'title' => 'Mister three',
        'field_node_one_ref' => 'f4924e8b-133b-4d37-b25b-542341850639',
        'status' => FALSE,
      ],
    ];
    $this->createNodes('test_nodetype_two', $test_nodetype_two);
    $view = Views::getView('node_reverse_reference');
    $this->executeView($view);
    $this->assertCount(3, $view->result);

    // Publish the new node and verify the referenced node is in the result.
    $this->entities['1e6561c6-5078-4502-95ca-21b8f17d772a']->setPublished();
    $this->entities['1e6561c6-5078-4502-95ca-21b8f17d772a']->save();
    $view = Views::getView('node_reverse_reference');
    $this->executeView($view);
    $this->assertCount(4, $view->result);
    $expected_uuids = [
      'f4924e8b-133b-4d37-b25b-542341850639',
      'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
      'dc2edcbe-8fca-4b26-9850-c5fc77c0c1e0',
      '40894f20-922f-4564-ad68-19b67d4520f5',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
  }
}
