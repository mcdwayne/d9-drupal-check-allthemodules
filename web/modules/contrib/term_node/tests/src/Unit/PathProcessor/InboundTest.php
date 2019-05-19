<?php

namespace Drupal\Tests\term_node\Unit\PathProcessor;

use Drupal\term_node\PathProcessor\Inbound;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;


/**
 * Tests inbound alter
 *
 * @group term_node
 *
 * @coversDefaultClass \Drupal\term_node\PathProcessor\Inbound
 */
class InboundTest extends UnitTestCase {

  /**
   * @covers ::processInbound
   * @dataProvider getTestPaths
   */
  public function testProcess($in, $out, $no_redirect) {
    $request = Request::create('/');

    // Mock the alias manager.
    $alias_manager = $this->getMockBuilder('\Drupal\Core\Path\AliasManagerInterface')
      ->getMock();
    $alias_manager->method('getPathByAlias')
      ->willReturn($in);

    $term_resolver = $this->getMockBuilder('\Drupal\term_node\TermResolverInterface')
      ->getMock();
    $node_resolver = $this->getMockBuilder('\Drupal\term_node\NodeResolverInterface')
      ->getMock();

    $term_resolver->method('getPath')
      ->willReturn('/node/1');
    $node_resolver->method('getReferencedBy')
      ->willReturn(1);

    $inbound_path = new Inbound($alias_manager, $term_resolver, $node_resolver);

    $path = $inbound_path->processInbound($in, $request);

    // Test that the path is returned, changed if needed.
    $this->assertEquals($out, $path);

    // Test redirect is off if changed.
    $redirect_disabled = $request->attributes->get('_disable_route_normalizer');
    $this->assertEquals($no_redirect, $redirect_disabled);
  }

  /**
   * Data provider for testProcessOutbound().
   */
  public function getTestPaths() {
    return [
      ['/taxonomy/term/2', '/node/1', TRUE],
      ['/taxonomy/term/2/edit', '/taxonomy/term/2/edit', NULL],
      ['/taxonomy/term/2/preview', '/taxonomy/term/2/preview', NULL],
      ['/entity/3', '/entity/3', NULL],
    ];
  }

}
