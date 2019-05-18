<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\freelinking\DrupalOrg;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * Tests the drupalorg plugin.
 *
 * @group freelinking
 */
class DrupalOrgTest extends UnitTestCase {

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
    $tProphet->translateString(Argument::any())->willReturn('Click to view on drupal.org.');
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
    $plugin = $this->getPlugin();
    $this->assertEquals('Click to view on drupal.org.', $plugin->getTip()->render());
  }

  /**
   * Assert that the indicator is functional.
   *
   * @param string $indicator
   *   The indicator string.
   * @param array $settings
   *   The settings to test.
   * @param int $expected
   *   The expected value from preg_match.
   *
   * @dataProvider indicatorProvider
   */
  public function testGetIndicator($indicator, array $settings, $expected) {
    $plugin = $this->getPlugin($settings);
    $this->assertEquals($expected, preg_match($plugin->getIndicator(), $indicator));
  }

  /**
   * Asserts that default configuration is expected.
   */
  public function testDefaultConfiguration() {
    $plugin = $this->getPlugin();
    $expected = [
      'settings' => [
        'scrape' => TRUE,
        'node' => TRUE,
        'project' => TRUE,
      ],
    ];
    $this->assertArrayEquals($expected, $plugin->defaultConfiguration());
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
      'dest' => 'freelinking',
      'indicator' => 'drupalproject',
      'language' => NULL,
      'text' => '',
    ];
    $expected = [
      '#type' => 'link',
      '#title' => new TranslatableMarkup(
        'Drupal.org: “@title”',
        ['@title' => 'Freelinking'],
        [],
        $this->translationInterfaceMock
      ),
      '#url' => Url::fromUri(
        'https://drupal.org/project/freelinking',
        ['absolute' => TRUE, 'language' => NULL]
      ),
      '#attributes' => [
        'title' => new TranslatableMarkup(
          'Click to view on drupal.org.',
          [],
          [],
          $this->translationInterfaceMock
        ),
      ],
    ];

    // Assert that 200 Response with title.
    $this->assertArrayEquals($expected, $plugin->buildLink($target));
  }

  /**
   * Get plugin instance.
   *
   * @param array $default_settings
   *   The settings to use.
   *
   * @return \Drupal\freelinking\Plugin\freelinking\DrupalOrg
   *   A plugin instance.
   */
  protected function getPlugin(array $default_settings = []) {
    $settings = $default_settings + [
      'scrape' => TRUE,
      'node' => TRUE,
      'project' => TRUE,
    ];

    $configuration = ['settings' => $settings];
    $plugin_definition = [
      'id' => 'drupalorg',
      'title' => 'Drupal.org External link',
      'hidden' => FALSE,
      'weight' => 0,
    ] + $configuration;
    return DrupalOrg::create($this->container, $configuration, 'external', $plugin_definition);
  }

  /**
   * Create Guzzle client instance with mock handlers.
   *
   * @return \GuzzleHttp\Client
   *   The Guzzle HTTP Client.
   */
  protected function getGuzzleMock() {
    $mock = new MockHandler([
      new Response(200, ['Content-Type' => 'text/html'], '<body><h1 class="page-subtitle">Freelinking</h1><div>Test Page Content.</div></body>'),
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
      ['nomatch', ['node' => TRUE], 0],
      ['nomatch', ['project' => TRUE], 0],
      ['dorg', ['node' => TRUE], 1],
      ['dorg', ['node' => FALSE], 0],
      ['drupalorg', ['node' => TRUE], 1],
      ['drupalo', ['node' => TRUE], 1],
      ['drupalproject', ['project' => TRUE], 1],
      ['drupalp', ['project' => TRUE], 1],
      ['dproject', ['project' => TRUE], 1],
      ['drupalproject', ['project' => FALSE], 0],
    ];
  }

}
