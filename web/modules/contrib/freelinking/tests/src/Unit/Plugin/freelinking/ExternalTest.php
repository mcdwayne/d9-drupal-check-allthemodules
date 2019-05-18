<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\freelinking\External;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * Tests the external plugin.
 *
 * @group freelinking
 */
class ExternalTest extends UnitTestCase {

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * String translation mock.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationInterfaceMock;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Mock string translation service.
    $tProphet = $this->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $tProphet->translateString(Argument::any())->willReturn('Click to visit an external URL.');
    $this->translationInterfaceMock = $tProphet->reveal();

    // Mock module handler service.
    $moduleHandlerProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandlerProphet->moduleExists('search')->willReturn(FALSE);

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->translationInterfaceMock);
    $this->container->set('module_handler', $moduleHandlerProphet->reveal());
    $this->container->set('http_client', $this->getGuzzleMock());
    \Drupal::setContainer($this->container);
  }

  /**
   * Assert that getTip() is functional.
   */
  public function testGetTip() {
    $plugin = $this->getPlugin(FALSE);
    $this->assertEquals('Click to visit an external URL.', $plugin->getTip()->render());
  }

  /**
   * Assert that the indicator is functional.
   *
   * @param string $indicator
   *   The indicator string.
   * @param int $expected
   *   The expected value from preg_match.
   *
   * @dataProvider indicatorProvider
   */
  public function testGetIndicator($indicator, $expected) {
    $plugin = $this->getPlugin(FALSE);
    $this->assertEquals($expected, preg_match($plugin->getIndicator(), $indicator));
  }

  /**
   * Asserts that default configuration is expected.
   */
  public function testDefaultConfiguration() {
    $plugin = $this->getPlugin();
    $this->assertArrayEquals(['settings' => ['scrape' => TRUE]], $plugin->defaultConfiguration());
  }

  /**
   * Asserts that buildLink returns appropriate render array.
   *
   * A data provider is not used for this test because Guzzle mocking is a bit
   * weird and unorthodox.
   */
  public function testBuildLink() {
    $plugin = $this->getPlugin();
    $target = [
      'dest' => '//www.example.com',
      'indicator' => 'http',
      'language' => NULL,
      'text' => '',
    ];
    $expected = [
      '#type' => 'link',
      '#title' => new TranslatableMarkup(
        'Ext. link: “@title”',
        ['@title' => 'Test Page'],
        [],
        $this->translationInterfaceMock
      ),
      '#url' => Url::fromUri(
        'http://www.example.com',
        ['absolute' => TRUE, 'language' => NULL]
      ),
      '#attributes' => [
        'title' => new TranslatableMarkup(
          'Click to visit an external URL.',
          [],
          [],
          $this->translationInterfaceMock
        ),
      ],
    ];

    // Assert that 200 Response with title.
    $this->assertArrayEquals($expected, $plugin->buildLink($target));

    // Assert that 200 Response without title has URL as title.
    $expected['#title'] = 'http://www.example.com';
    $this->assertArrayEquals($expected, $plugin->buildLink($target));

    // Assert that RequestException is handled correctly.
    $error_expected = [
      '#theme' => 'freelink_error',
      '#plugin' => 'external',
      '#message' => new TranslatableMarkup(
        'External target “@url” not found',
        ['@url' => 'http://www.example.com'],
        [],
        $this->translationInterfaceMock
      ),
    ];
    $this->assertArrayEquals($error_expected, $plugin->buildLink($target));

    // Assert that 200 Response with title is not displayed when text is set.
    $target['text'] = 'Custom Title';
    $expected['#title'] = 'Custom Title';
    $this->assertArrayEquals($expected, $plugin->buildLink($target));
  }

  /**
   * Get plugin instance.
   *
   * @param bool $scrapeOption
   *   The scrape option.
   *
   * @return \Drupal\freelinking\Plugin\freelinking\External
   *   A plugin instance.
   */
  protected function getPlugin($scrapeOption = TRUE) {
    $configuration = ['settings' => ['scrape' => $scrapeOption]];
    $plugin_definition = [
      'id' => 'external',
      'title' => 'External links',
      'hidden' => FALSE,
      'weight' => 0,
    ] + $configuration;
    return External::create($this->container, $configuration, 'external', $plugin_definition);
  }

  /**
   * Create Guzzle client instance with mock handlers.
   *
   * @return \GuzzleHttp\Client
   *   The Guzzle HTTP Client.
   */
  protected function getGuzzleMock() {
    $errorResponse = new Response(404, ['Content-Type' => 'text/plain, charset=UTF-8'], '404 Error: Page not found');
    $mock = new MockHandler([
      new Response(200, ['Content-Type' => 'text/html'], '<body><h1 class="page-title">Test Page</h1><div>Test Page Content.</div></body>'),
      new Response(200, ['Content-Type' => 'text/html'], '<body>A bunch of text without a page title</body>'),
      $errorResponse,
      new RequestException(
        'Server Error',
        new Request('GET', 'test'),
        $errorResponse
      ),
    ]);
    $handler = HandlerStack::create($mock);

    return new Client(['handler' => $handler]);
  }

  /**
   * Provide test parameters for ::testGetIndicator.
   *
   * @return array
   *   An array of test parameters.
   */
  public function indicatorProvider() {
    return [
      ['nomatch', 0],
      ['http', 1],
      ['https', 1],
      ['ext', 1],
      ['external', 1],
    ];
  }

}
