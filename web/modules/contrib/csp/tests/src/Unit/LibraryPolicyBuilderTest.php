<?php

namespace Drupal\Tests\csp\Unit;

use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\csp\LibraryPolicyBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\csp\LibraryPolicyBuilder
 * @group csp
 */
class LibraryPolicyBuilderTest extends UnitTestCase {

  /**
   * Memory Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Mock Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * Mock Active Theme.
   *
   * @var \Drupal\Core\Theme\ActiveTheme|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $activeTheme;

  /**
   * Mock Theme Handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeHandler;

  /**
   * Mock Library Discovery.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->cache = new MemoryBackend();

    $this->moduleHandler = $this->getMockBuilder(ModuleHandler::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->themeHandler = $this->getMockBuilder(ThemeHandler::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->libraryDiscovery = $this->getMockBuilder(LibraryDiscovery::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Test an empty extension set.
   *
   * @covers ::getSources
   * @covers ::getExtensionSources
   * @covers ::getLibrarySources
   */
  public function testEmptyPolicy() {
    $this->themeHandler->expects($this->atLeastOnce())
      ->method('listInfo')
      ->willReturn([]);
    $this->moduleHandler->expects($this->atLeastOnce())
      ->method('getModuleList')
      ->willReturn([]);

    $this->libraryDiscovery->expects($this->atLeastOnce())
      ->method('getLibrariesByExtension')
      ->with('core')
      ->willReturn([]);

    $libraryPolicy = new LibraryPolicyBuilder($this->cache, $this->moduleHandler, $this->themeHandler, $this->libraryDiscovery);

    $this->assertArrayEquals(
      [],
      $libraryPolicy->getSources()
    );
  }

  /**
   * Test that a library's external sources are discovered.
   *
   * @covers ::getSources
   * @covers ::getExtensionSources
   * @covers ::getLibrarySources
   * @covers ::getHostFromUri
   */
  public function testLibraryWithSources() {

    $this->moduleHandler->expects($this->any())
      ->method('getModuleList')
      ->willReturn([]);
    $this->themeHandler->expects($this->any())
      ->method('listInfo')
      ->willReturn([
        'stark' => (object) ['name' => 'stark'],
      ]);

    $extensionMap = [
      ['core', []],
      ['stark', ['test' => []]],
    ];
    $this->libraryDiscovery->expects($this->any())
      ->method('getLibrariesByExtension')
      ->willReturnMap($extensionMap);

    // Test a few behaviours:
    // - local files are ignored.
    // - script domains are sorted.
    // - duplicate style domains are filtered.
    $libraryInfo = [
      'js' => [
        [
          'type' => 'file',
          'data' => 'js/script.js',
        ],
        [
          'type' => 'external',
          'data' => 'http://js.example.org/js/script.js',
        ],
        [
          'type' => 'external',
          'data' => 'http://js.example.com/js/script.js',
        ],
      ],
      'css' => [
        [
          'type' => 'external',
          'data' => 'http://css.example.com/css/style1.css',
        ],
        [
          'type' => 'external',
          'data' => 'http://css.example.com/css/style2.css',
        ],
      ],
    ];
    $this->libraryDiscovery->expects($this->atLeastOnce())
      ->method('getLibraryByName')
      ->with('stark', 'test')
      ->willReturn($libraryInfo);

    $libraryPolicy = new LibraryPolicyBuilder($this->cache, $this->moduleHandler, $this->themeHandler, $this->libraryDiscovery);

    $this->assertArrayEquals(
      [
        'script-src' => ['js.example.com', 'js.example.org'],
        'script-src-elem' => ['js.example.com', 'js.example.org'],
        'style-src' => ['css.example.com'],
        'style-src-elem' => ['css.example.com'],
      ],
      $libraryPolicy->getSources()
    );
  }

}
