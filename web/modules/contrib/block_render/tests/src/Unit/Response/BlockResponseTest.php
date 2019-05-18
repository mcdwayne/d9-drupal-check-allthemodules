<?php
/**
 * @file
 * Contains Drupal\Tests\block_render\Unit\Response\BlockResponseTest.
 */

namespace Drupal\Tests\block_render\Unit\Response;

use Drupal\block_render\Response\BlockResponse;
use Drupal\Tests\UnitTestCase;

/**
 * Test The asset response data.
 *
 * @group block_render
 */
class BlockResponseTest extends UnitTestCase {

  /**
   * Assets.
   *
   * @var \Drupal\block_render\Response\AssetResponseInterface
   */
  protected $assets;

  /**
   * Content.
   *
   * @var \Drupal\block_render\Content\RenderedContentInterface
   */
  protected $content;

  /**
   * Block Response.
   *
   * @var \Drupal\block_render\Response\BlockResponse
   */
  protected $blockResponse;

  /**
   * Create the Block Response object.
   */
  public function setUp() {
    $this->assets = $this->getMockBuilder('Drupal\block_render\Response\AssetResponseInterface')
      ->getMock();
    $this->content = $this->getMockBuilder('Drupal\block_render\Content\RenderedContentInterface')
      ->getMock();

    $this->blockResponse = new BlockResponse($this->assets, $this->content);
  }

  /**
   * Tests getting assets.
   */
  public function testGetAssets() {
    $this->assertEquals($this->assets, $this->blockResponse->getAssets());
  }

  /**
   * Tests getting content.
   */
  public function testGetContent() {
    $this->assertEquals($this->content, $this->blockResponse->getContent());
  }

  /**
   * Tests setting a property directly.
   */
  public function testSetFailure() {
    $this->setExpectedException('LogicException', 'You cannot set properties.');
    $this->blockResponse->test = '';
  }

}
