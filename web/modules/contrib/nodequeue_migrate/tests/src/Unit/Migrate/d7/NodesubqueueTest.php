<?php

namespace Drupal\Tests\nodequeue_migrate\Unit\Migrate\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 nodesubqueue source plugin.
 *
 * @group nodequeue
 */
class NodesubqueueTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\nodequeue_migrate\Plugin\migrate\source\d7\NodeSubqueue';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_nodesubqueue',
    ],
  ];

  protected $expectedResults = [
    [
      'sqid' => 1,
      'qid' => 1,
      'reference' => '1',
      'title' => 'Subqueue example 1',
      'name' => 'queue_example_1',
      'items' => [
        1 => 5,
        2 => 6,
      ],
      'sq_name' => 'queue_example_1',
    ],
    [
      'sqid' => 2,
      'qid' => 2,
      'reference' => '2',
      'title' => 'Subqueue example 2',
      'name' => 'queue_parent_example',
      'items' => [
        1 => 1,
      ],
      'sq_name' => '2',
    ],
    [
      'sqid' => 3,
      'qid' => 2,
      'reference' => '3',
      'title' => 'Subqueue example 3',
      'name' => 'queue_parent_example',
      'items' => [
        1 => 2,
        2 => 3,
      ],
      'sq_name' => '3',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $subqueue = [];
    $queue = [];
    $nodes = [];
    foreach ($this->expectedResults as $expected_result) {
      $subqueue[] = [
        'sqid' => $expected_result['sqid'],
        'qid' => $expected_result['qid'],
        'reference' => $expected_result['reference'],
        'title' => $expected_result['title'],
      ];
      $queue[$expected_result['qid']] = [
        'qid' => $expected_result['qid'],
        'name' => $expected_result['name'],
      ];
      foreach ($expected_result['items'] as $position => $nid) {
        $nodes[] = [
          'qid' => $expected_result['qid'],
          'sqid' => $expected_result['sqid'],
          'nid' => $nid,
          'position' => $position,
        ];
      }
    }
    $this->databaseContents['nodequeue_subqueue'] = $subqueue;
    $this->databaseContents['nodequeue_queue'] = $queue;
    $this->databaseContents['nodequeue_nodes'] = $nodes;
    parent::setUp();
  }

}
