<?php

namespace Drupal\Tests\entity_pilot\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests prepare passengers event fired from entity_pilot.customs service.
 *
 * @group entity_pilot
 */
class BookCalculateDependenciesEventTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot',
    'serialization',
    'rest',
    'hal',
    'node',
    'user',
    'text',
    'system',
    'book',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installSchema('book', 'book');
    $this->installEntitySchema('user');
  }

  /**
   * Tests prepare passengers event for book content.
   */
  public function testPreparePassengersEvent() {
    /** @var \Drupal\entity_pilot\BaggageHandlerInterface $baggage_handler */
    $baggage_handler = $this->container->get('entity_pilot.baggage_handler');
    $top_level_node = Node::create([
      'type' => 'book',
      'title' => 'Top level node',
      'status' => 1,
      'uid' => 1,
      'book' => [
        'bid' => 'new',
      ],
    ]);
    $top_level_node->save();
    $top_level_node_id = $top_level_node->id();
    $child_node = Node::create([
      'type' => 'book',
      'title' => 'Second level node',
      'status' => 1,
      'uid' => 1,
      'book' => [
        'bid' => $top_level_node_id,
        'pid' => $top_level_node_id,
      ],
    ]);
    $child_node->save();
    $grandchild_node = Node::create([
      'type' => 'book',
      'title' => 'Third level node',
      'status' => 1,
      'uid' => 1,
      'book' => [
        'bid' => $top_level_node_id,
        'pid' => $child_node->id(),
      ],
    ]);
    $grandchild_node->save();
    $great_grandchild_node = Node::create([
      'type' => 'book',
      'title' => 'Fourth level node',
      'status' => 1,
      'uid' => 1,
      'book' => [
        'bid' => $top_level_node_id,
        'pid' => $grandchild_node->id(),
      ],
    ]);
    $great_grandchild_node->save();
    $tags = [];
    $dependencies = $baggage_handler->calculateDependencies($child_node, $tags);
    $this->assertEquals([$top_level_node->uuid()], array_keys($dependencies));
    $dependencies = $baggage_handler->calculateDependencies($grandchild_node, $tags);
    $this->assertEquals([$child_node->uuid(), $top_level_node->uuid()], array_keys($dependencies));
    $dependencies = $baggage_handler->calculateDependencies($great_grandchild_node, $tags);
    $this->assertEquals([
      $grandchild_node->uuid(),
      $child_node->uuid(),
      $top_level_node->uuid(),
    ], array_keys($dependencies));
  }

}
