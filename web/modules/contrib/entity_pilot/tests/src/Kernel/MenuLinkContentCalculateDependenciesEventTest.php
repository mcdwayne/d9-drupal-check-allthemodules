<?php

namespace Drupal\Tests\entity_pilot\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests prepare passengers event fired from entity_pilot.customs service.
 *
 * @group entity_pilot
 */
class MenuLinkContentCalculateDependenciesEventTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot',
    'serialization',
    'menu_link_content',
    'rest',
    'hal',
    'node',
    'user',
    'text',
    'system',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('menu_link_content');
  }

  /**
   * Tests prepare passengers event for menu link content.
   */
  public function testPreparePassengersEvent() {
    /** @var \Drupal\entity_pilot\BaggageHandlerInterface $baggage_handler */
    $baggage_handler = $this->container->get('entity_pilot.baggage_handler');
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();
    $node = Node::create([
      'type' => 'article',
      'title' => 'Some node',
      'status' => 1,
      'uid' => 1,
    ]);
    $node->save();
    $another_node = Node::create([
      'type' => 'article',
      'title' => 'Some other node',
      'status' => 1,
      'uid' => 1,
    ]);
    $another_node->save();
    $parent_link = MenuLinkContent::create([
      'title' => 'A menu link to another node',
      'link' => [['uri' => 'entity:node/' . $another_node->id()]],
      'menu_name' => 'tools',
    ]);
    $parent_link->save();
    $link = MenuLinkContent::create([
      'title' => 'A menu link to a node',
      'link' => [['uri' => 'entity:node/' . $node->id()]],
      'menu_name' => 'tools',
      'parent' => 'menu_link_content:' . $parent_link->uuid(),
    ]);
    $link->save();
    $tags = [];
    $dependencies = $baggage_handler->calculateDependencies($link, $tags);
    $this->assertEquals([
      $node->uuid(),
      $parent_link->uuid(),
      $another_node->uuid(),
    ], array_keys($dependencies));
  }

}
