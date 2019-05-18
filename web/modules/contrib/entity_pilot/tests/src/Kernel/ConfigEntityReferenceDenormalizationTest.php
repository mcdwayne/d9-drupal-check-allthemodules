<?php

namespace Drupal\Tests\entity_pilot\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests config entity denormalizing.
 *
 * @group entity_pilot
 */
class ConfigEntityReferenceDenormalizationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot_config_entity_test',
    'entity_pilot',
    'serialization',
    'hal',
    'node',
    'user',
    'rest',
    'system',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
  }

  /**
   * Tests denormalization.
   */
  public function testDenormalization() {
    $node_type = NodeType::create([
      'type' => 'test',
      'name' => 'Some node type',
    ]);
    $node_type->save();
    $node = Node::create([
      'type' => 'test',
      'title' => 'Some node',
      'type2' => 'test',
      'uid' => 0,
      'status' => 1,
    ]);

    $serializer = $this->container->get('serializer');
    $normalized = $serializer->normalize($node, 'hal_json');
    $denormalized = $serializer->denormalize($normalized, $node->getEntityType()->getClass(), 'hal_json');
    $this->assertEquals('Some node', $denormalized->title->value);
    $this->assertEquals('test', $denormalized->type2->target_id);
  }

}
