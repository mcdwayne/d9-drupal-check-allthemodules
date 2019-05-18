<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\freelinking\Search;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the search plugin.
 *
 * @group freelinking
 */
class SearchTest extends UnitTestCase {

  /**
   * Search plugin.
   *
   * @var \Drupal\freelinking\Plugin\freelinking\Search
   */
  protected $plugin;

  /**
   * Translation interface mock.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationInterfaceMock;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Mock the string translation.
    $tProphet = $this->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $tProphet
      ->translateString(Argument::any())
      ->willReturn('Search this site for content like “%dest”.');
    $this->translationInterfaceMock = $tProphet->reveal();

    // Mock path validator.
    $pathValidatorProphet = $this->prophesize('\Drupal\Core\Path\PathValidatorInterface');

    // Mock module handler.
    $moduleHandlerProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandlerProphet->moduleExists('search')->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->translationInterfaceMock);
    $container->set('module_handler', $moduleHandlerProphet->reveal());
    $container->set('path.validator', $pathValidatorProphet->reveal());
    \Drupal::setContainer($container);

    $plugin_definition = [
      'id' => 'search',
      'title' => 'Search',
      'hidden' => FALSE,
      'weight' => 0,
      'settings' => ['failover' => 'error'],
    ];
    $configuration = ['settings' => ['failover' => 'error']];
    $this->plugin = Search::create($container, $configuration, 'search', $plugin_definition);
  }

  /**
   * Assert that indicator functions for both google and core search.
   *
   * @param string $indicator
   *   The indicator string.
   * @param int $expected
   *   The expected output from preg_match.
   *
   * @dataProvider indicatorProvider
   */
  public function testGetIndicator($indicator, $expected) {
    $this->assertEquals($expected, preg_match($this->plugin->getIndicator(), $indicator));
  }

  /**
   * Asserts that the appropriate tip is returned.
   */
  public function testGetTip() {
    $this->assertEquals('Search this site for content like “%dest”.', $this->plugin->getTip());
  }

  /**
   * Asserts that buildLink is functional when search enabled.
   */
  public function testBuildLink() {
    $expected = [
      '#type' => 'link',
      '#title' => 'Search Test',
      '#url' => Url::fromUri(
        'base:search/node',
        [
          'language' => NULL,
          'query' => ['keys' => 'A+search+string'],
        ]
      ),
      '#attributes' => [
        'title' => new TranslatableMarkup(
          'Search this site for content.',
          [],
          [],
          $this->translationInterfaceMock
        ),
      ],
    ];
    $target = [
      'target' => 'search:A search string|Search Test',
      'dest' => 'A search string',
      'text' => 'Search Test',
      'language' => NULL,
    ];
    $this->assertArrayEquals($expected, $this->plugin->buildLink($target));
  }

  /**
   * Provides test parameters for ::testGetIndicator.
   *
   * @return array
   *   An array of test parameters.
   */
  public function indicatorProvider() {
    return [
      ['nomatch', 0],
      ['search', 1],
    ];
  }

}
