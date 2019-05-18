<?php

namespace Drupal\Tests\hn\Functional;

use Drupal\node\Entity\Node;

/**
 * Provides some basic tests with permissions of the HN module.
 *
 * @group hn
 */
class HnContentTest extends HnFunctionalTestBase {

  public static $modules = [
    'hn_test',
  ];

  /**
   * Tests a normal node without any references and only the default view mode.
   */
  public function testBasicNode() {
    $response = $this->getHnJsonResponse('/node/1');
    $r = $response['data'][$response['paths']['/node/1']];
    $defaults = hn_test_node_base(1);

    // Assure all data is as expected.
    $this->assertEquals($r['__hn'], [
      'view_modes' => ['default'],
      'entity' => [
        'type' => 'node',
        'bundle' => 'hn_test_basic_page',
      ],
      // TODO: Remove base_path from urls, see issue #2916393.
      'url' => base_path() . 'node/1',
      'status' => 200,
    ]);
    $this->assertEquals($r['title'], $defaults['title']);
    $this->assertEquals($r['body']['value'], $defaults['body']);
    $this->assertEquals($r['field_link'], [
      'uri' => 'https://www.google.com',
      'title' => '',
      'options' => [],
    ]);
    $this->assertEquals($r['field_reference'], []);
    $this->assertEquals($r['field_reference_teaser'], []);

    // Make sure teaser fields are not available in the default view mode.
    $this->assertFalse(isset($r['field_teaser_body']));
  }

  /**
   * Tests a reference to another node with the default view mode.
   */
  public function testDefaultReference() {
    $response = $this->getHnJsonResponse('/node/2');
    $node2 = $response['data'][$response['paths']['/node/2']];
    $node1_reference = $node2['field_reference'][0];

    $this->assertEquals($node1_reference['target_id'], 1);
    $this->assertEquals($node1_reference['target_type'], 'node');
    // TODO: Remove base_path from urls, see issue #2916393.
    $this->assertEquals($node1_reference['url'], base_path() . 'node/1');
    $this->assertEquals($node2['field_link']['uri'], base_path() . 'node/1');

    $node1 = $response['data'][$node1_reference['target_uuid']];

    $this->assertEquals($node1['__hn']['view_modes'], ['default']);
    $this->assertTrue(isset($node1['body']));
    $this->assertFalse(isset($node1['field_teaser_body']));
  }

  /**
   * Tests a reference to another node with a teaser view mode.
   */
  public function testTeaserReference() {
    $response = $this->getHnJsonResponse('/node/3');
    $node3 = $response['data'][$response['paths']['/node/3']];
    $node1_reference = $node3['field_reference_teaser'][0];
    $node1 = $response['data'][$node1_reference['target_uuid']];

    $this->assertEquals($node1['__hn']['view_modes'], ['teaser']);
    $this->assertFalse(isset($node1['body']));
    $this->assertEquals($node1['field_teaser_body'], hn_test_node_base(1)['field_teaser_body']);
  }

  /**
   * This tests chained references.
   *
   * To test this, it gets node 4. Node 4 has a reference to node 2. Node 2 has
   * a reference to node 1. Node 1 has a reference to node 4. All three nodes
   * should only have the 'default' display mode.
   */
  public function testChainedReferences() {
    $node1 = Node::load(1);
    $node1->set('field_reference', Node::load(4));
    $node1->save();

    $response = $this->getHnJsonResponse('/node/4');

    foreach ([1, 2, 4] as $nodeId) {
      // TODO: Remove base_path from urls, see issue #2916393.
      $node = $response['data'][$response['paths'][base_path() . 'node/' . $nodeId]];
      $this->assertEquals($node['__hn']['view_modes'], ['default']);
      $this->assertTrue(isset($node['body']));
      $this->assertFalse(isset($node['field_teaser_body']));
    }
  }

  /**
   * This tests an entity that is referenced with two different view modes.
   */
  public function testMultipleViewModes() {
    $response = $this->getHnJsonResponse('/node/5');

    $node5 = $response['data'][$response['paths']['/node/5']];

    $this->assertEquals($node5['field_reference'], $node5['field_reference_teaser']);

    $node1 = $response['data'][$node5['field_reference'][0]['target_uuid']];
    $expected = hn_test_node_base(1);
    $view_modes = $node1['__hn']['view_modes'];
    sort($view_modes);

    $this->assertEquals(['default', 'teaser'], $view_modes);
    $this->assertEquals($expected['body'], $node1['body']['value']);
    $this->assertEquals($expected['field_teaser_body'], $node1['field_teaser_body']);
  }

}
