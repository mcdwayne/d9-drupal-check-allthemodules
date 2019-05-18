<?php

namespace Drupal\Tests\csp\Unit\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Render\HtmlResponse;
use Drupal\csp\EventSubscriber\ResponseCspSubscriber;
use Drupal\csp\LibraryPolicyBuilder;
use Drupal\csp\ReportingHandlerPluginManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @coversDefaultClass \Drupal\csp\EventSubscriber\ResponseCspSubscriber
 * @group csp
 */
class ResponseCspSubscriberTest extends UnitTestCase {

  /**
   * Mock HTTP Response.
   *
   * @var \Drupal\Core\Render\HtmlResponse|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $response;

  /**
   * Mock Response Event.
   *
   * @var \Symfony\Component\HttpKernel\Event\FilterResponseEvent|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $event;

  /**
   * Mock Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The Library Policy service.
   *
   * @var \Drupal\csp\LibraryPolicyBuilder|\PHPUnit_Framework_MockObject_MockObject
   */
  private $libraryPolicy;

  /**
   * The Reporting Handler Plugin Manager service.
   *
   * @var \Drupal\csp\ReportingHandlerPluginManager|\PHPUnit_Framework_MockObject_MockObject
   */
  private $reportingHandlerPluginManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->response = $this->getMockBuilder(HtmlResponse::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->response->headers = $this->getMockBuilder(ResponseHeaderBag::class)
      ->disableOriginalConstructor()
      ->getMock();
    $responseCacheableMetadata = $this->getMockBuilder(CacheableMetadata::class)
      ->getMock();
    $this->response->method('getCacheableMetadata')
      ->willReturn($responseCacheableMetadata);

    /** @var \Symfony\Component\HttpKernel\Event\FilterResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
    $this->event = $this->getMockBuilder(FilterResponseEvent::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->event->expects($this->any())
      ->method('isMasterRequest')
      ->willReturn(TRUE);
    $this->event->expects($this->any())
      ->method('getResponse')
      ->willReturn($this->response);

    $this->moduleHandler = $this->getMockBuilder(ModuleHandler::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->libraryPolicy = $this->getMockBuilder(LibraryPolicyBuilder::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->reportingHandlerPluginManager = $this->getMockBuilder(ReportingHandlerPluginManager::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Check that the subscriber listens to the Response event.
   *
   * @covers ::getSubscribedEvents
   */
  public function testSubscribedEvents() {
    $this->assertArrayHasKey(KernelEvents::RESPONSE, ResponseCspSubscriber::getSubscribedEvents());
  }

  /**
   * Check the policy with CSS optimization disabled.
   *
   * @covers ::onKernelResponse
   */
  public function testUnoptimizedResponse() {

    // IE9 workaround was removed in 8.7.0.
    // @see https://www.drupal.org/node/2993171
    if (version_compare(\Drupal::VERSION, '8.7', '<')) {
      $this->markTestSkipped("Test for drupal/core >=8.7 skipped");
    }

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'system.performance' => [
        'css.preprocess' => FALSE,
      ],
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'self',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            'style-src' => [
              'base' => 'self',
            ],
          ],
        ],
        'enforce' => [
          'enable' => FALSE,
        ],
      ],
    ]);

    $this->libraryPolicy->expects($this->any())
      ->method('getSources')
      ->willReturn([]);

    $subscriber = new ResponseCspSubscriber($configFactory, $this->moduleHandler, $this->libraryPolicy, $this->reportingHandlerPluginManager);

    $this->response->headers->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo('Content-Security-Policy-Report-Only'),
        $this->equalTo("script-src 'self' 'unsafe-inline'; style-src 'self'")
      );
    $this->response->getCacheableMetadata()
      ->expects($this->once())
      ->method('addCacheTags')
      ->with(['config:csp.settings']);

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Check the policy with CSS optimization disabled in Drupal <=8.6.
   *
   * @covers ::onKernelResponse
   */
  public function testUnoptimizedResponse86() {

    // IE9 workaround was removed in 8.7.0.
    // @see https://www.drupal.org/node/2993171
    if (version_compare(\Drupal::VERSION, '8.7', '>=')) {
      $this->markTestSkipped("Test for drupal/core <=8.6 skipped");
    }

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'system.performance' => [
        'css.preprocess' => FALSE,
      ],
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'self',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            'style-src' => [
              'base' => 'self',
            ],
          ],
        ],
        'enforce' => [
          'enable' => FALSE,
        ],
      ],
    ]);

    $this->libraryPolicy->expects($this->any())
      ->method('getSources')
      ->willReturn([]);

    $subscriber = new ResponseCspSubscriber($configFactory, $this->moduleHandler, $this->libraryPolicy, $this->reportingHandlerPluginManager);

    $this->response->headers->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo('Content-Security-Policy-Report-Only'),
        $this->equalTo("script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'")
      );
    $this->response->getCacheableMetadata()
      ->expects($this->once())
      ->method('addCacheTags')
      ->with(['config:csp.settings']);

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Check the policy with CSS optimization disabled if IE9 module installed.
   *
   * @covers ::onKernelResponse
   */
  public function testUnoptimizedResponseIe9() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'system.performance' => [
        'css.preprocess' => FALSE,
      ],
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'self',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            'style-src' => [
              'base' => 'self',
            ],
          ],
        ],
        'enforce' => [
          'enable' => FALSE,
        ],
      ],
    ]);

    $this->moduleHandler->expects($this->any())
      ->method('moduleExists')
      ->with($this->equalTo('ie9'))
      ->willReturn(TRUE);

    $this->libraryPolicy->expects($this->any())
      ->method('getSources')
      ->willReturn([]);

    $subscriber = new ResponseCspSubscriber($configFactory, $this->moduleHandler, $this->libraryPolicy, $this->reportingHandlerPluginManager);

    $this->response->headers->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo('Content-Security-Policy-Report-Only'),
        $this->equalTo("script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'")
      );

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Check the policy with CSS optimization enabled.
   *
   * @covers ::onKernelResponse
   */
  public function testOptimizedResponse() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'system.performance' => [
        'css.preprocess' => TRUE,
      ],
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'self',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            'style-src' => [
              'base' => 'self',
            ],
          ],
        ],
        'enforce' => [
          'enable' => FALSE,
        ],
      ],
    ]);

    $this->libraryPolicy->expects($this->any())
      ->method('getSources')
      ->willReturn([]);

    $subscriber = new ResponseCspSubscriber($configFactory, $this->moduleHandler, $this->libraryPolicy, $this->reportingHandlerPluginManager);

    $this->response->headers->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo('Content-Security-Policy-Report-Only'),
        $this->equalTo("script-src 'self' 'unsafe-inline'; style-src 'self'")
      );

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Check the policy with enforcement enabled.
   *
   * @covers ::onKernelResponse
   */
  public function testEnforcedResponse() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'system.performance' => [
        'css.preprocess' => TRUE,
      ],
      'csp.settings' => [
        'enforce' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'self',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            'style-src' => [
              'base' => 'self',
            ],
          ],
        ],
        'report-only' => [
          'enable' => FALSE,
        ],
      ],
    ]);

    $this->libraryPolicy->expects($this->any())
      ->method('getSources')
      ->willReturn([]);

    $subscriber = new ResponseCspSubscriber($configFactory, $this->moduleHandler, $this->libraryPolicy, $this->reportingHandlerPluginManager);

    $this->response->headers->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo('Content-Security-Policy'),
        $this->equalTo("script-src 'self' 'unsafe-inline'; style-src 'self'")
      );

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Check the generated headers with both policies enabled.
   *
   * @covers ::onKernelResponse
   */
  public function testBothPolicies() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'system.performance' => [
        'css.preprocess' => TRUE,
      ],
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'any',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            'style-src' => [
              'base' => 'any',
              'flags' => [
                'unsafe-inline',
              ],
            ],
          ],
        ],
        'enforce' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'self',
            ],
            'style-src' => [
              'base' => 'self',
            ],
          ],
        ],
      ],
    ]);

    $this->libraryPolicy->expects($this->any())
      ->method('getSources')
      ->willReturn([]);

    $subscriber = new ResponseCspSubscriber($configFactory, $this->moduleHandler, $this->libraryPolicy, $this->reportingHandlerPluginManager);

    $this->response->headers->expects($this->exactly(2))
      ->method('set')
      ->withConsecutive(
        [
          $this->equalTo('Content-Security-Policy-Report-Only'),
          $this->equalTo("script-src 'unsafe-inline' *; style-src 'unsafe-inline' *"),
        ],
        [
          $this->equalTo('Content-Security-Policy'),
          $this->equalTo("script-src 'self'; style-src 'self'"),
        ]
      );

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Test that library sources are included.
   *
   * @covers ::onKernelResponse
   */
  public function testWithLibraryDirective() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'system.performance' => [
        'css.preprocess' => TRUE,
      ],
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'any',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            'style-src' => [
              'base' => 'self',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            'style-src-elem' => [
              'base' => 'self',
            ],
          ],
        ],
      ],
    ]);

    $this->libraryPolicy->expects($this->any())
      ->method('getSources')
      ->willReturn([
        'style-src' => ['example.com'],
        'style-src-elem' => ['example.com'],
      ]);

    $subscriber = new ResponseCspSubscriber($configFactory, $this->moduleHandler, $this->libraryPolicy, $this->reportingHandlerPluginManager);

    $this->response->headers->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo('Content-Security-Policy-Report-Only'),
        $this->equalTo("script-src 'unsafe-inline' *; style-src 'self' 'unsafe-inline' example.com; style-src-elem 'self' example.com")
      );

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Test that library sources do not override a disabled directive.
   *
   * @covers ::onKernelResponse
   */
  public function testDisabledLibraryDirective() {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'system.performance' => [
        'css.preprocess' => TRUE,
      ],
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'any',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            'style-src' => [
              'base' => 'self',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            // style-src-elem is purposefully omitted.
          ],
        ],
      ],
    ]);

    $this->libraryPolicy->expects($this->any())
      ->method('getSources')
      ->willReturn([
        'style-src' => ['example.com'],
        'style-src-elem' => ['example.com'],
      ]);

    $subscriber = new ResponseCspSubscriber($configFactory, $this->moduleHandler, $this->libraryPolicy, $this->reportingHandlerPluginManager);

    $this->response->headers->expects($this->once())
      ->method('set')
      ->with(
        $this->equalTo('Content-Security-Policy-Report-Only'),
        $this->equalTo("script-src 'unsafe-inline' *; style-src 'self' 'unsafe-inline' example.com")
      );

    $subscriber->onKernelResponse($this->event);
  }

}
