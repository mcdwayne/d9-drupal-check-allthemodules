<?php

namespace Drupal\Tests\term_node\Unit\PathProcessor;

use Drupal\term_node\PathProcessor\Outbound;
use Drupal\Tests\UnitTestCase;

/**
 * Tests outbound alter
 *
 * @group term_node
 *
 * @coversDefaultClass \Drupal\term_node\PathProcessor\Outbound
 */
class OutboundTest extends UnitTestCase {

  /**
   * @covers ::processOutbound
   * @dataProvider getTestPaths
   */
  public function testProcessOutbound($path, $expected) {

    // Mock the alias manager.
    $alias_manager = $this->getMockBuilder('\Drupal\Core\Path\AliasManagerInterface')
      ->getMock();
    $alias_manager->method('getPathByAlias')
      ->willReturn($path);

    // Mock the node resolver.
    $node_resolver = $this->getMockBuilder('\Drupal\term_node\NodeResolverInterface')
      ->getMock();
    $node_resolver->method('getPath')
      ->willReturn('/bar');

    $outbound = new Outbound($alias_manager, $node_resolver);
    $path = $outbound->processOutbound('/foo');

    // Test that only internal paths for nodes being viewed get changed.
    $this->assertEquals($expected, $path);
  }

  /**
   * Data provider for testProcessOutbound().
   */
  public function getTestPaths() {
    return [
      ['/node/1', '/bar'],
      ['/node/1/edit', '/foo'],
      ['/node/1/preview', '/foo'],
      ['/taxonomy/term/1', '/foo'],
      ['/taxonomy/term/1/edit', '/foo'],
      ['/entity/1', '/foo'],
    ];
  }

}
