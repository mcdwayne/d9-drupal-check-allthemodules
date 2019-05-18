<?php

namespace Drupal\Tests\nodequeue_migrate\Unit\Migrate\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 nodequeue source plugin.
 *
 * @group nodequeue
 */
class NodequeueTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\nodequeue_migrate\Plugin\migrate\source\d7\NodeQueue';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_nodequeue',
    ],
  ];

  protected $expectedResults = [
    [
      'qid' => 1,
      'title' => 'Queue example 1',
      'size' => 0,
      'name' => 'queue_example_1',
      'target_bundles' => ['page', 'blog'],
      'handler' => 'simple',
    ],
    [
      'qid' => 2,
      'title' => 'Queue parent example',
      'size' => 99,
      'name' => 'queue_parent_example',
      'target_bundles' => ['page'],
      'handler' => 'multiple',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $queue = [];
    foreach ($this->expectedResults as $expected_result) {
      $queue[] = [
        'qid' => $expected_result['qid'],
        'title' => $expected_result['title'],
        'size' => $expected_result['size'],
        'name' => $expected_result['name'],
      ];
    }
    $this->databaseContents['nodequeue_queue'] = $queue;
    $this->databaseContents['nodequeue_types'] = [
      [
        'qid' => 1,
        'type' => 'page',
      ],
      [
        'qid' => 1,
        'type' => 'blog',
      ],
      [
        'qid' => 2,
        'type' => 'page',
      ],
    ];
    $this->databaseContents['nodequeue_subqueue'] = [
      [
        'sqid' => 1,
        'qid' => 1,
      ],
      [
        'sqid' => 2,
        'qid' => 2,
      ],
      [
        'sqid' => 3,
        'qid' => 2,
      ],
    ];
    parent::setUp();
  }

}
