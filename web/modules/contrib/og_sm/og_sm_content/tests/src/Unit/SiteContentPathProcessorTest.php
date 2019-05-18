<?php

namespace Drupal\Tests\og_sm_content\Unit;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\NodeInterface;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\og_sm_content\PathProcessor\SiteContentPathProcessor;
use Drupal\og_sm_path\SitePathManagerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the SiteContentPathProcessor service.
 *
 * @group og_sm
 * @coversDefaultClass \Drupal\og_sm_content\PathProcessor\SiteContentPathProcessor
 */
class SiteContentPathProcessorTest extends UnitTestCase {

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $request;

  /**
   * The site path processor service.
   *
   * @var \Drupal\og_sm_path\PathProcessor\SitePathProcessor|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $sitePathProcessor;

  /**
   * Value object used for bubbleable rendering metadata.
   *
   * @var \Drupal\Core\Render\BubbleableMetadata|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $bubbleableMetadata;


  /**
   * An array of test nodes.
   *
   * @var \Drupal\node\NodeInterface[]|\Prophecy\Prophecy\ObjectProphecy[]
   */
  protected $testNodes;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    /* @var \Drupal\og_sm_path\SitePathManagerInterface $site_path_manager */
    $site_path_manager = $this->prophesize(SitePathManagerInterface::class);
    /* @var \Drupal\og_sm\SiteManagerInterface $site_manager */
    $site_manager = $this->prophesize(SiteManagerInterface::class);

    foreach ($this->getNodePaths() as $path => $node_id) {
      if ($node_id) {
        /* @var \Drupal\node\NodeInterface $node */
        $node = $this->prophesize(NodeInterface::class);
        $node->id()->willReturn($node_id);
        $this->testNodes[$node_id] = $node;
        $site_path_manager->getSiteFromPath($path)->willReturn($node);
        $site_path_manager->getPathFromSite($node)->willReturn($path);
        $site_manager->load($node_id)->willReturn($node);
      }
      else {
        $site_path_manager->getSiteFromPath($path)->willReturn(FALSE);
      }
    }

    $this->request = $this->prophesize(Request::class)->reveal();
    $this->bubbleableMetadata = $this->prophesize(BubbleableMetadata::class)->reveal();

    $site_manager->currentSite()->willReturn($this->testNodes[1]);
    $site_manager->load(Argument::any())->willReturn(FALSE);

    $this->sitePathProcessor = new SiteContentPathProcessor($site_path_manager->reveal(), $site_manager->reveal());
  }

  /**
   * Tests the SitePathProcessor::processInbound() method.
   *
   * @param string $original_path
   *   The original path.
   * @param string $expected_path
   *   The expected path after the inbound alter.
   *
   * @covers ::processInbound
   * @dataProvider processInboundProvider
   */
  public function testProcessInbound($original_path, $expected_path) {
    $path = $this->sitePathProcessor->processInbound($original_path, $this->request);
    $this->assertEquals($expected_path, $path);
  }

  /**
   * Tests the SitePathProcessor::processOutboundbound() method.
   *
   * @param string $original_path
   *   The original path.
   * @param string $expected_path
   *   The expected path after the inbound alter.
   *
   * @covers ::processOutbound
   * @dataProvider processOutboundProvider
   */
  public function testProcessOutbound($original_path, $expected_path) {
    $options = [];
    $path = $this->sitePathProcessor->processOutbound($original_path, $options, $this->request, $this->bubbleableMetadata);
    $this->assertEquals($expected_path, $path);
  }

  /**
   * Gets an array of node ids keyed with their path.
   *
   * @return array
   *   An array of node ids keyed with their path.
   */
  protected function getNodePaths() {
    return [
      '/test-site-path' => 1,
      '/content/test/45687266' => FALSE,
    ];
  }

  /**
   * Data provider for testProcessInbound().
   *
   * @see ::testProcessInbound()
   */
  public function processInboundProvider() {
    return [
      [
        '/content/test/45687266/content/add',
        '/content/test/45687266/content/add',
      ],
      [
        '/test-site-path/content/add',
        '/group/node/1/content/add',
      ],
      [
        '/test-site-path/content/add/article',
        '/group/node/1/content/add/article',
      ],
    ];
  }

  /**
   * Data provider for testProcessOutboundbound().
   *
   * @see ::testProcessOutbound()
   */
  public function processOutboundProvider() {
    return [
      [
        '/content/test/45687266/content/add',
        '/content/test/45687266/content/add',
      ],
      [
        '/group/node/1/content/add',
        '/test-site-path/content/add',
      ],
      [
        '/group/node/1/content/add/article',
        '/test-site-path/content/add/article',
      ],
    ];
  }

}
