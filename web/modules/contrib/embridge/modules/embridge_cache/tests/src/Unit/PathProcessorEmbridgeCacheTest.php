<?php
/**
 * @file
 * Contains \Drupal\Tests\embridge_cache\Unit\PathProcessorEmbridgeCacheTest.
 */

namespace Drupal\Tests\embridge_cache\Unit;


use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\embridge_cache\PathProcessor\PathProcessorEmbridgeCache;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PathProcessorEmbridgeCacheTest.
 *
 * @package Drupal\Tests\embridge_cache\Unit
 *
 * @group embridge
 *
 * @coversDefaultClass \Drupal\embridge_cache\PathProcessor\PathProcessorEmbridgeCache
 */
class PathProcessorEmbridgeCacheTest extends UnitTestCase {

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $streamWrapperManager;

  /**
   * Our plugin to test.
   *
   * @var \Drupal\embridge_cache\PathProcessor\PathProcessorEmbridgeCache
   */
  protected $pathProcessor;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->streamWrapperManager = $this->getMockBuilder(StreamWrapperManagerInterface::class)->disableOriginalConstructor()->getMock();
    $this->streamWrapperManager->expects($this->once())
      ->method('getViaScheme')
      ->with('public')
      ->willReturn(new TestStream());

    $this->pathProcessor = new PathProcessorEmbridgeCache($this->streamWrapperManager);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Tests processInbound with a non cache path.
   *
   * @test
   */
  public function processInboundDoesNothingForNonCachePaths() {
    $path = '/some/path/somewhere/or/other';
    /** @var Request|\PHPUnit_Framework_MockObject_MockObject $mock_request */
    $mock_request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
    $mock_request->expects($this->never())
      ->method('set');

    $this->assertEquals($path, $this->pathProcessor->processInbound($path, $mock_request));
  }

  /**
   * Tests processInbound with a non cache path.
   *
   * @test
   */
  public function processInboundDoesNothingForCachePathsWithNotEnoughParts() {
    $path = '/sites/default/files/embridge_cache/test/123';
    /** @var Request|\PHPUnit_Framework_MockObject_MockObject $mock_request */
    $mock_request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
    $mock_request->expects($this->never())
      ->method('set');

    $this->assertEquals($path, $this->pathProcessor->processInbound($path, $mock_request));
  }

  /**
   * Tests processInbound with a non cache path.
   *
   * @test
   */
  public function processInboundSetsFileOnRequestWithOver3Parts() {
    $path = '/sites/default/files/embridge_cache/test_catalog/thumbnail/public/this/cat/rocks/cat.png';
    $expected_path = '/sites/default/files/embridge_cache/test_catalog/thumbnail/public';
    $request = new Request();

    $this->assertEquals($expected_path, $this->pathProcessor->processInbound($path, $request));
    $this->assertEquals('this/cat/rocks/cat.png', $request->query->get('file'));
  }

}
