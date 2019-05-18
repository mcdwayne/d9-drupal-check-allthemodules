<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\freelinking\GoogleSearch;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the google search plugin.
 *
 * @group freelinking
 */
class GoogleSearchTest extends UnitTestCase {

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
      ->willReturn('Search google for the specified terms.');
    $this->translationInterfaceMock = $tProphet->reveal();

    // Mock path validator.
    $pathValidatorProphet = $this->prophesize('\Drupal\Core\Path\PathValidatorInterface');

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->translationInterfaceMock);
    $container->set('path.validator', $pathValidatorProphet->reveal());
    \Drupal::setContainer($container);

    $plugin_definition = [
      'id' => 'google',
      'title' => 'Google Search',
      'hidden' => FALSE,
      'weight' => 1,
    ];
    $this->plugin = new GoogleSearch([], 'google', $plugin_definition);
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
    $this->assertEquals('Search google for the specified terms.', $this->plugin->getTip());
  }

  /**
   * Asserts the failover functionality for the google search plugin.
   */
  public function testBuildLink() {

    $expected = [
      '#type' => 'link',
      '#title' => 'Google Search Test Search',
      '#url' => Url::fromUri(
        'https://google.com/search',
        [
          'absolute' => TRUE,
          'query' => ['q' => 'Test+Search', 'hl' => 'en'],
          'language' => NULL,
        ]
      ),
      '#attributes' => [
        'title' => new TranslatableMarkup(
          'Search google for the specified terms.',
          [],
          [],
          $this->translationInterfaceMock
        ),
      ],
    ];
    $target = [
      'target' => 'google:Test Search|Test Search',
      'dest' => 'Test Search',
      'text' => 'Test Search',
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
      ['google', 1],
    ];
  }

}
