<?php
/**
 * @file
 * Containers \Drupal\block_render\Theme\RenderNegotiator.
 */

namespace Drupal\Tests\block_render\Unit\Theme;

use Drupal\block_render\Theme\RenderNegotiator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Theme Negotiation for Block Rendering.
 *
 * @group block_render
 */
class RenderNegotiatorTest extends UnitTestCase {

  /**
   * Tests the applies method.
   */
  public function testApplies() {
    $route_match = $this->getMockBuilder('Drupal\Core\Routing\RouteMatchInterface')
      ->getMock();
    $route_match->expects($this->once())
      ->method('getRouteName')
      ->will($this->returnValue('block_render.block'));

    $negotiator = new RenderNegotiator();
    $response = $negotiator->applies($route_match);

    $this->assertEquals(TRUE, $response);
  }

  /**
   * Tests the applies method faiulre.
   */
  public function testAppliesFailure() {
    $route_match = $this->getMockBuilder('Drupal\Core\Routing\RouteMatchInterface')
      ->getMock();
    $route_match->expects($this->once())
      ->method('getRouteName')
      ->will($this->returnValue('test_module.test_path'));

    $negotiator = new RenderNegotiator();
    $response = $negotiator->applies($route_match);

    $this->assertEquals(FALSE, $response);
  }

  /**
   * Tests determining active theme.
   */
  public function testDetermineActiveTheme() {
    $block = $this->getMockBuilder('Drupal\block\BlockInterface')
      ->getMock();
    $block->expects($this->once())
      ->method('getTheme')
      ->will($this->returnValue('test'));

    $route_match = $this->getMockBuilder('Drupal\Core\Routing\RouteMatchInterface')
      ->getMock();
    $route_match->expects($this->once())
      ->method('getParameter')
      ->with('block')
      ->will($this->returnValue($block));

    $negotiator = new RenderNegotiator();
    $response = $negotiator->determineActiveTheme($route_match);

    $this->assertEquals('test', $response);
  }

}
