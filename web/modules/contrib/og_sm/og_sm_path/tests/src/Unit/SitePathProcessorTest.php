<?php

namespace Drupal\Tests\og_sm_path\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\node\NodeInterface;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\og_sm_path\EventSubscriber\EventSubscriber;
use Drupal\og_sm_path\PathProcessor\SitePathProcessor;
use Drupal\og_sm_path\SitePathManagerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the SitePathProcessor service.
 *
 * @group og_sm
 * @coversDefaultClass \Drupal\og_sm_path\PathProcessor\SitePathProcessor
 */
class SitePathProcessorTest extends UnitTestCase {

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $request;

  /**
   * The site path manager.
   *
   * @var \Drupal\og_sm_path\SitePathManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $sitePathManager;

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $siteManager;

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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\Prophecy\Prophecy\ObjectProphecy[]
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->sitePathManager = $this->prophesize(SitePathManagerInterface::class);
    $this->siteManager = $this->prophesize(SiteManagerInterface::class);
    $this->eventDispatcher = new EventDispatcher();
    $this->eventDispatcher->addSubscriber(new EventSubscriber());

    /* @var \Drupal\Core\Utility\UnroutedUrlAssemblerInterface|\Prophecy\Prophecy\ObjectProphecy $unrouted_url_assembler */
    // We need to mock the unrouted_url_assembler service to allow the
    // Url::fromUserInput method to work.
    $unrouted_url_assembler = $this->prophesize(UnroutedUrlAssemblerInterface::class);

    foreach ($this->getNodePaths() as $path => $node_id) {
      if ($node_id) {
        /* @var \Drupal\node\NodeInterface $node */
        $node = $this->prophesize(NodeInterface::class);
        $node->id()->willReturn($node_id);
        $this->testNodes[$node_id] = $node;
        $this->sitePathManager->getSiteFromPath($path)->willReturn($node);
        $this->sitePathManager->getPathFromSite($node)->willReturn($path);
        $this->siteManager->load($node_id)->willReturn($node);

        $unrouted_url_assembler
          ->assemble(Argument::containingString('base:group/node/' . $node_id), [], FALSE)
          ->will(function ($args) use ($node_id, $path) {
            $uri = substr($args[0], 5);
            return str_replace('group/node/' . $node_id, $path, $uri);
          });

      }
      else {
        $this->sitePathManager->getSiteFromPath($path)->willReturn(FALSE);
      }
    }

    $unrouted_url_assembler
      ->assemble(Argument::any(), [], FALSE)
      ->will(function ($args) {
        return str_replace('base:', '/', $args[0]);
      });

    $container = new ContainerBuilder();
    $container->set('path.validator', $this->prophesize(PathValidatorInterface::class)->reveal());
    $container->set('unrouted_url_assembler', $unrouted_url_assembler->reveal());
    \Drupal::setContainer($container);

    $this->request = $this->prophesize(Request::class)->reveal();
    $this->request->query = new ParameterBag();

    $this->bubbleableMetadata = $this->prophesize(BubbleableMetadata::class)->reveal();

    $this->siteManager->currentSite()->willReturn($this->testNodes[1]);
    $this->siteManager->load(Argument::any())->willReturn(FALSE);

    $this->sitePathProcessor = new SitePathProcessor($this->sitePathManager->reveal(), $this->siteManager->reveal(), $this->eventDispatcher);
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
   * @param array $original_options
   *   The original options array.
   * @param string $expected_path
   *   The expected path after the inbound alter.
   * @param array $expected_options
   *   The expected options array.
   *
   * @covers ::processOutbound
   * @dataProvider processOutboundProvider
   */
  public function testProcessOutbound($original_path, array $original_options, $expected_path, array $expected_options) {
    $path = $this->sitePathProcessor->processOutbound($original_path, $original_options, $this->request, $this->bubbleableMetadata);
    $this->assertEquals($expected_path, $path);
    $this->assertArrayEquals($expected_options, $original_options);
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
        '/content/test/45687266/admin/content',
        '/content/test/45687266/admin/content',
      ],
      [
        '/test-site-path/admin/people/edit/456',
        '/group/node/1/admin/people/edit/456',
      ],
      [
        '/test-site-path/entity_reference_autocomplete/node/foo/123',
        '/entity_reference_autocomplete/node/foo/123',
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
        '/group/node/987654321/admin/content',
        [
          'query' => [
            'destination' => '/group/node/987654321/admin/content',
          ],
        ],
        '/group/node/987654321/admin/content',
        [
          'query' => [
            'destination' => '/group/node/987654321/admin/content',
          ],
        ],
      ],
      [
        '/test-site-path/admin/test/me/456',
        [],
        '/test-site-path/admin/test/me/456',
        [],
      ],
      [
        '/group/node/1/admin/test/me/456',
        [
          'query' => [
            'destination' => '/group/node/1/admin/test/me/456',
          ],
        ],
        '/test-site-path/admin/test/me/456',
        [
          'query' => [
            'destination' => '/test-site-path/admin/test/me/456',
          ],
        ],
      ],
      [
        '/entity_reference_autocomplete/node/foo/123',
        [],
        '/test-site-path/entity_reference_autocomplete/node/foo/123',
        [],
      ],
    ];
  }

}
