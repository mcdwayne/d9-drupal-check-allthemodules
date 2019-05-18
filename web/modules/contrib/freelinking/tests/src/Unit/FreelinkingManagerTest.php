<?php

namespace Drupal\Tests\freelinking\Unit;

use Drupal\Core\Cache\NullBackend;
use Drupal\freelinking\FreelinkingManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the freelinking plugin manager.
 *
 * @group freelinking
 */
class FreelinkingManagerTest extends UnitTestCase {

  /**
   * Freelinking Manager object to run tests on.
   *
   * @var \Drupal\freelinking\FreelinkingManagerInterface
   *   The Freelinking Manager.
   */
  protected $pluginManager;

  /**
   * Mock language object.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   *   A language object.
   */
  protected $language;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Use a null cache backend to prevent caching.
    $cacheBackend = new NullBackend('freelinking');

    // Mock the module handler and language objects.
    $moduleProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandler = $moduleProphet->reveal();
    $languageProphet = $this->prophesize('\Drupal\Core\Language\LanguageInterface');
    $this->language = $languageProphet->reveal();
    $languageManagerProphet = $this->prophesize('\Drupal\Core\Language\LanguageManagerInterface');
    $languageManagerProphet->getLanguage('en')->willReturn($this->language);
    $languageManager = $languageManagerProphet->reveal();

    $namespaces = new \ArrayObject();

    $this->pluginManager = new FreelinkingManager($namespaces, $cacheBackend, $moduleHandler, $languageManager);
  }

  /**
   * Tests parseTarget method.
   *
   * @param array $expected
   *   The expected destination string.
   * @param string $target
   *   The target string.
   *
   * @dataProvider parseTargetProvider
   */
  public function testParseTarget(array $expected, $target) {
    $expected['target'] = $target;
    $expected['language'] = $this->language;

    $this->assertEquals($expected, $this->pluginManager->parseTarget($target, 'en'));
  }

  /**
   * Provide test parameters and expected values for testParseTarget().
   *
   * @return array
   *   An array of test parameters and expected values.
   */
  public function parseTargetProvider() {
    return [
      [
        [
          'dest' => 'nid:2',
          'text' => 'Special title',
          'tooltip' => 'tooltip',
          'other' => [],
        ],
        'nid:2|Special title|tooltip',
      ],
      [
        [
          'dest' => 'nid:2',
          'text' => NULL,
          'tooltip' => NULL,
          'other' => [],
        ],
        'nid:2',
      ],
      [
        [
          'dest' => 'external:http://example.com?id=12345',
          'text' => '1',
          'tooltip' => NULL,
          'other' => [],
        ],
        'external:http://example.com?id=12345|1',
      ],
      [
        [
          'dest' => 'external:http://example.com?id=12345&q=%E2%99%A5#fragment',
          'text' => 'Title',
          'tooltip' => 'Tooltip',
          'arbitrary' => 'value',
          'other' => ['Other'],
        ],
        'external:http://example.com?id=12345&q=%E2%99%A5#fragment|Title|Tooltip|arbitrary=value|Other',
      ],
    ];
  }

}
