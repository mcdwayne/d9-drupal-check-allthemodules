<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\freelinking\File;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the freelinking file plugin.
 *
 * @group freelinking
 */
class FileTest extends UnitTestCase {

  /**
   * Translation interface mock.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationInterfaceMock;

  /**
   * Dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Mock string translation service.
    $tProphet = $this->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $tProphet->translateString(Argument::any())->willReturn('Click to view a local file.');
    $this->translationInterfaceMock = $tProphet->reveal();

    // Mock Immutable Config.
    $configProphet = $this->prophesize('\Drupal\Core\Config\ImmutableConfig');
    $configProphet->get('default_scheme')->willReturn('public');

    // Mock configuration factory.
    $configFactoryProphet = $this->prophesize('\Drupal\Core\Config\ConfigFactoryInterface');
    $configFactoryProphet
      ->get('system.file')
      ->willReturn($configProphet->reveal());

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->translationInterfaceMock);
    $this->container->set('module_handler', $this->getModuleHandlerMock());
    $this->container->set('config.factory', $configFactoryProphet->reveal());
    $this->container->set('stream_wrapper_manager', $this->getStreamWrapperMock());
    \Drupal::setContainer($this->container);
  }

  /**
   * Assert that getIndicator is functional.
   */
  public function testGetIndicator() {
    $plugin = $this->getPlugin();
    $this->assertEquals(1, preg_match($plugin->getIndicator(), 'file'));
  }

  /**
   * Assert that getTip is functional.
   */
  public function testGetTip() {
    $plugin = $this->getPlugin();
    $this->assertEquals('Click to view a local file.', $plugin->getTip()->render());
  }

  /**
   * Assert that defaultConfiguration is functional.
   */
  public function testDefaultConfiguration() {
    $expected = [
      'settings' => ['scheme' => 'public'],
    ];
    $plugin = $this->getPlugin();
    $this->assertArrayEquals($expected, $plugin->defaultConfiguration());
  }

  /**
   * Assert that buildLink is functional.
   *
   * @param string $path
   *   The file path.
   * @param int $availability
   *   The file availability.
   *
   * @dataProvider buildLinkProvider
   */
  public function testBuildLink($path, $availability) {

    // Reset the module_handler and stream_wrapper_manager services based on the
    // data for the test.
    $this->container->set('module_handler', $this->getModuleHandlerMock($availability));
    $this->container->set('stream_wrapper_manager', $this->getStreamWrapperMock($availability));
    \Drupal::setContainer($this->container);

    // Setup expected result.
    if ($availability > 0) {
      $expected = [
        '#type' => 'link',
        '#title' => 'Test File',
        '#url' => Url::fromUri('http://example.com/logo.png', ['absolute' => TRUE, 'language' => NULL]),
        '#attributes' => [
          'title' => new TranslatableMarkup(
            'Click to view a local file.',
            [],
            [],
            $this->translationInterfaceMock
          ),
        ],
      ];
    }
    else {
      $expected = [
        '#theme' => 'freelink_error',
        '#plugin' => 'file',
        '#message' => new TranslatableMarkup(
          'File @name not found',
          ['@name' => 'logo.png'],
          [],
          $this->translationInterfaceMock
        ),
      ];
    }

    $target = [
      'target' => 'file:logo.png|Test File',
      'dest' => 'logo.png',
      'text' => 'Test File',
      'language' => NULL,
    ];
    $plugin = $this->getPlugin();
    $this->assertArrayEquals($expected, $plugin->buildLink($target));
  }

  /**
   * Provide data for ::testBuildLink.
   *
   * @return array
   *   An array of test parameters.
   */
  public function buildLinkProvider() {
    return [
      ['logo.png', 0],
      ['logo.png', -1],
      ['logo.png', 1],
    ];
  }

  /**
   * Get the plugin to test.
   *
   * @return \Drupal\freelinking\Plugin\freelinking\File
   *   The file plugin.
   */
  protected function getPlugin() {
    $plugin_definition = [
      'id' => 'file',
      'title' => 'File',
      'hidden' => FALSE,
      'weight' => 0,
      'settings' => ['scheme' => 'public'],
    ];
    return File::create($this->container, ['settings' => ['scheme' => 'public']], 'file', $plugin_definition);
  }

  /**
   * Get the stream wrapper mock depending on if the file should exist or not.
   *
   * @param int $fileAvailability
   *   Whether the file should not exist (0), exist (1) or not be accessible
   *   (-1).
   *
   * @return \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   *   The stream wrapper manager object mocke to return
   */
  protected function getStreamWrapperMock($fileAvailability = 0) {
    // Mock Stream Wrapper Manager and stream wrapper interface.
    $streamWrapperProphet = $this->prophesize('\Drupal\Core\StreamWrapper\StreamWrapperInterface');
    $streamWrapperProphet->setUri(Argument::any())->will(function () {
      return $this;
    });
    $streamWrapperProphet->realpath()->will(function () use ($fileAvailability) {
      return $fileAvailability !== 0;
    });
    $streamWrapperProphet->getExternalUrl()->willReturn('http://example.com/logo.png');

    $streamWrapperManagerProphet = $this->prophesize('\Drupal\Core\StreamWrapper\StreamWrapperManagerInterface');
    $streamWrapperManagerProphet->getViaScheme(Argument::any())->willReturn($streamWrapperProphet->reveal());

    return $streamWrapperManagerProphet->reveal();
  }

  /**
   * Get a mock of the module handler service depending on file_download result.
   *
   * @param int $fileAvailability
   *   Whether the file should not exist (0), exist (1) or not be accessible
   *   (-1).
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service mock.
   */
  protected function getModuleHandlerMock($fileAvailability = 0) {
    // Mock module handler service.
    $moduleHandlerProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandlerProphet
      ->invokeAll('file_download', Argument::any())
      ->will(function ($args) use ($fileAvailability) {
        $ret = [];
        if ($fileAvailability === -1) {
          $ret['file'] = -1;
        }
        return $ret;
      });
    return $moduleHandlerProphet->reveal();
  }

}
