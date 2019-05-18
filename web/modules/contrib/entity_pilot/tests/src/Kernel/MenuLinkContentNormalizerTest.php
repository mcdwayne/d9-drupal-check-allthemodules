<?php

namespace Drupal\Tests\entity_pilot\Kernel;

use Drupal\entity_pilot\ArrivalInterface;
use Drupal\entity_pilot\Normalizer\MenuLinkContentNormalizer;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests menu link content normalizer.
 *
 * @group entity_pilot
 */
class MenuLinkContentNormalizerTest extends KernelTestBase {

  use UserCreationTrait;

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
    $this->installSchema('system', ['router', 'sequences']);
    $this->installSchema('node', 'node_access');
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests prepare passengers event.
   */
  public function testMenuLinkNormalizer() {
    \Drupal::service('current_user')->setAccount($this->createUser());
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
    $clone = clone $node;
    $node->save();
    $parent = MenuLinkContent::create([
      'title' => 'A front page menu link',
      'link' => [['uri' => 'internal:/']],
      'menu_name' => 'tools',
    ]);
    $parent->save();
    $link = MenuLinkContent::create([
      'title' => 'A menu link to a node',
      'link' => [['uri' => 'entity:node/' . $node->id()]],
      'menu_name' => 'tools',
      'parent' => 'menu_link_content:' . $parent->uuid(),
    ]);
    $link->save();
    $serializer = $this->container->get('serializer');
    $link_manager = $this->container->get('rest.link_manager');
    $mock_field_uri = $link_manager->getRelationUri('menu_link_content', 'menu_link_content', MenuLinkContentNormalizer::PSUEDO_FIELD_NAME, []);
    $parent_field_uri = $link_manager->getRelationUri('menu_link_content', 'menu_link_content', MenuLinkContentNormalizer::PSUEDO_PARENT_FIELD_NAME, []);
    $node_url = $node->toUrl('canonical', ['absolute' => TRUE])->setRouteParameter('_format', 'hal_json')->toString();
    $parent_url = $parent->toUrl('canonical', ['absolute' => TRUE])->setRouteParameter('_format', 'hal_json')->toString();
    $context['included_fields'] = ['uuid'];
    $embedded = $serializer->normalize($node, 'hal_json', $context);
    $embedded_parent = $serializer->normalize($parent, 'hal_json', $context);
    $normalized = $serializer->normalize($link, 'hal_json');
    $this->assertEquals($node->uuid(), $normalized['link'][0]['target_uuid']);
    $this->assertEquals([$embedded], $normalized['_embedded'][$mock_field_uri]);
    $this->assertEquals([['href' => $node_url]], $normalized['_links'][$mock_field_uri]);
    $this->assertEquals([$embedded_parent], $normalized['_embedded'][$parent_field_uri]);
    $this->assertEquals([['href' => $parent_url]], $normalized['_links'][$parent_field_uri]);
    $this->assertEquals('menu_link_content:' . $parent->uuid(), $normalized['parent'][0]['value']);
    $parent_normalized = $serializer->normalize($parent, 'hal_json');

    // Now we switch the URI to something else but it should still go back to
    // the same node.
    $normalized['link'][0]['link'] = 'entity:node/' . ($node->id() + 1);
    $unsaved_uuid_resolver = $this->container->get('entity_pilot.resolver.unsaved_uuid');
    $unsaved_uuid_resolver->add($clone);
    $parent_id = $link->getParentId();
    $link_uuid = $link->uuid();

    // Clean up.
    $node->delete();
    $link->delete();
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $denormalized */
    $denormalized = $serializer->denormalize($normalized, MenuLinkContent::class, 'hal_json');
    $clone->save();
    $denormalized->save();
    $clone_url = $clone->toUrl();
    $denormalized_url = $denormalized->getUrlObject();
    $this->assertEquals($clone_url->getRouteParameters(), $denormalized_url->getRouteParameters());
    $this->assertEquals($clone_url->getRouteName(), $denormalized_url->getRouteName());
    $this->assertEquals($parent_id, $denormalized->getParentId());

    // Test screening sorts the node before the menu-link.
    $passengers = [
      $link_uuid => $normalized,
      $parent->uuid() => $parent_normalized,
      $clone->uuid() => $serializer->normalize($node, 'hal_json'),
    ];

    $arrival = $this->createMock(ArrivalInterface::class);
    $arrival->expects($this->any())
      ->method('id')
      ->willReturn('1');

    $arrival->expects($this->any())
      ->method('getPassengers')
      ->willReturn($passengers);

    $deserialized = $this->container->get('entity_pilot.customs')->screen($arrival);
    $passenger_1 = reset($deserialized);
    $this->assertEquals('node', $passenger_1->getEntityTypeId());
    $this->assertEquals($node->uuid(), $passenger_1->uuid());
    $passenger_2 = next($deserialized);
    $this->assertEquals('menu_link_content', $passenger_2->getEntityTypeId());
    $this->assertEquals($parent->uuid(), $passenger_2->uuid());
    $passenger_3 = end($deserialized);
    $this->assertEquals('menu_link_content', $passenger_3->getEntityTypeId());
    $this->assertEquals($link->uuid(), $passenger_3->uuid());
  }

}
