<?php
/**
 * @file
 * Contains Drupal\block_render\BlockRenderOutboundPathProcessor.
 */

namespace Drupal\Tests\block_render\Unit;

use Drupal\block_render\BlockRenderOutboundPathProcessor;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Altering the outbound paths so they are always absolute.
 *
 * @group block_render
 */
class BlockRenderOutboundPathProcessorTest extends UnitTestCase {

  /**
   * Test the process outbound method.
   *
   * @dataProvider routes
   */
  public function testProcessOutbound($route_name, $absolute) {
    $route = $this->getMock('Drupal\Core\Routing\RouteMatchInterface');
    $route->expects($this->once())
      ->method('getRouteName')
      ->will($this->returnValue($route_name));
    $formats = ['test'];

    $processor = new BlockRenderOutboundPathProcessor($route, $formats);

    $path = '/testpath';
    $options = array();

    $response = $processor->processOutbound($path, $options);

    $this->assertEquals($path, $response);

    if ($absolute) {
      $this->assertArrayHasKey('absolute', $options);
      $this->assertTrue($options['absolute']);
    }
    else {
      $this->assertArrayNotHasKey('absolute', $options);
    }

  }

  /**
   * Data provider for processing outbound requests.
   */
  public function routes() {
    return [
      [
        'block_render.block',
        TRUE,
      ],
      [
        'rest.block_render.GET.test',
        TRUE,
      ],
      [
        'rest.block_render_multiple.GET.test',
        TRUE,
      ],
      [
        'testroute',
        FALSE,
      ],
    ];
  }

}
