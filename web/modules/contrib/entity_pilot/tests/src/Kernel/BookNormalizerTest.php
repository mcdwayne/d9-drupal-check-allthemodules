<?php

namespace Drupal\Tests\entity_pilot\Kernel;

use Drupal\entity_pilot\Normalizer\BookNormalizer;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests book normalizer.
 *
 * @group entity_pilot
 */
class BookNormalizerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot',
    'serialization',
    'book',
    'rest',
    'hal',
    'node',
    'user',
    'text',
    'system',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('book', 'book');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node']);
    $this->installConfig(['book']);
  }

  /**
   * Tests normalizing book node.
   */
  public function testBookLinks() {
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
      'title' => 'Top level node',
      'status' => 1,
      'uid' => 1,
      'book' => [
        'bid' => $top_level_node_id,
        'pid' => $top_level_node_id,
      ],
    ]);
    $child_node->save();
    $serializer = $this->container->get('serializer');
    $link_manager = $this->container->get('rest.link_manager');
    $mock_field_uri = $link_manager->getRelationUri('node', 'book', BookNormalizer::PSUEDO_PARENT_FIELD_NAME, []);
    $node_url = $top_level_node->toUrl('canonical', ['absolute' => TRUE])->setRouteParameter('_format', 'hal_json')->toString();
    $context['included_fields'] = ['uuid'];
    $embedded = $serializer->normalize($top_level_node, 'hal_json', $context);
    $normalized = $serializer->normalize($child_node, 'hal_json');
    $top_level_normalized = $serializer->normalize($top_level_node, 'hal_json');
    $this->assertEquals([$embedded], $normalized['_embedded'][$mock_field_uri]);
    $this->assertEquals($top_level_node->uuid(), $normalized['book']['bid']);
    $this->assertEquals($top_level_node->uuid(), $normalized['book']['pid']);
    $this->assertEquals([['href' => $node_url]], $normalized['_links'][$mock_field_uri]);
    $mock_field_uri = $link_manager->getRelationUri('node', 'book', BookNormalizer::PSUEDO_BOOK_FIELD_NAME, []);
    $node_url = $top_level_node->toUrl('canonical', ['absolute' => TRUE])->setRouteParameter('_format', 'hal_json')->toString();
    $context['included_fields'] = ['uuid'];
    $embedded = $serializer->normalize($top_level_node, 'hal_json', $context);
    $this->assertEquals([$embedded], $normalized['_embedded'][$mock_field_uri]);
    $this->assertEquals([['href' => $node_url]], $normalized['_links'][$mock_field_uri]);

    // Start fresh.
    $top_level_node->delete();
    $child_node->delete();
    $denormalized_top_level = $serializer->denormalize($top_level_normalized, Node::class, 'hal_json');
    $denormalized_top_level->save();
    $denormalized = $serializer->denormalize($normalized, Node::class, 'hal_json');
    $this->assertEquals($denormalized_top_level->id(), $denormalized->book['bid']);
    $this->assertEquals($denormalized_top_level->id(), $denormalized->book['pid']);
  }

}
