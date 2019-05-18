<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\freelinking\Wiki;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Test the freelinking wiki plugin.
 *
 * @group freelinking
 */
class WikiTest extends UnitTestCase {

  /**
   * The translation interface.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationInterfaceMock;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Mock the translation interface.
    $tProphet = $this->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $tProphet->translateString(Argument::any())->willReturn('Click to view a wiki page.');
    $this->translationInterfaceMock = $tProphet->reveal();

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->translationInterfaceMock);
    \Drupal::setContainer($this->container);
  }

  /**
   * Asserts that getTip is functional.
   */
  public function testGetTip() {
    $plugin = $this->getPlugin();
    $this->assertEquals('Click to view a wiki page.', $plugin->getTip()->render());
  }

  /**
   * Asserts that getIndicator is functional.
   *
   * @param string $indicator
   *   The indicator string.
   * @param int $expected
   *   The expected output from preg_match().
   *
   * @dataProvider indicatorProvider
   */
  public function testGetIndicator($indicator, $expected) {
    $plugin = $this->getPlugin();
    $this->assertEquals($expected, preg_match($plugin->getIndicator(), $indicator));
  }

  /**
   * Asserts that buildLink is functional.
   *
   * @param string $indicator
   *   The wiki to test.
   * @param string $destination
   *   The destination string.
   * @param string $langcode
   *   The language code to test.
   * @param string $expectedUrl
   *   The expected URL.
   *
   * @dataProvider buildLinkProvider
   */
  public function testBuildLink($indicator, $destination, $langcode, $expectedUrl) {
    $language = new Language(['id' => $langcode]);
    $expected = [
      '#type' => 'link',
      '#title' => 'Test Wiki',
      '#url' => Url::fromUri($expectedUrl, ['language' => $language, 'absolute' => TRUE]),
      '#attributes' => [
        'title' => new TranslatableMarkup(
          'Click to view a wiki page.',
          [],
          [],
          $this->translationInterfaceMock
        ),
      ],
    ];
    $target = [
      'indicator' => $indicator,
      'text' => 'Test Wiki',
      'target' => $indicator . ':' . $destination,
      'dest' => $destination,
      'language' => $language,
    ];

    $plugin = $this->getPlugin();
    $this->assertArrayEquals($expected, $plugin->buildLink($target));
  }

  /**
   * Provide test parameters for ::testGetIndicator().
   *
   * @return array
   *   An array of test parameters.
   */
  public function indicatorProvider() {
    return [
      ['wikipedia', 1],
      ['wp', 1],
      ['wikiquote', 1],
      ['wq', 1],
      ['wiktionary', 1],
      ['wt', 1],
      ['wikinews', 1],
      ['wn', 1],
      ['wikisource', 1],
      ['ws', 1],
      ['wikibooks', 1],
      ['wb', 1],
    ];
  }

  /**
   * Provide test parameters for ::testBuildLink().
   *
   * @return array
   *   An array of test parameters.
   */
  public function buildLinkProvider() {
    return [
      ['wikipedia', 'Main_Page', 'en', 'https://en.wikipedia.org/wiki/Main_Page'],
      ['wikipedia', 'Portada', 'es', 'https://es.wikipedia.org/wiki/Portada'],
      ['wp', 'Main_Page', 'en', 'https://en.wikipedia.org/wiki/Main_Page'],
      ['wikiquote', 'Main_Page', 'en', 'https://en.wikiquote.org/wiki/Main_Page'],
      ['wikiquote', 'Portada', 'es', 'https://es.wikiquote.org/wiki/Portada'],
      ['wq', 'Main_Page', 'en', 'https://en.wikiquote.org/wiki/Main_Page'],
      ['wiktionary', 'Main_Page', 'en', 'https://en.wiktionary.org/wiki/Main_Page'],
      ['wiktionary', 'Portada', 'es', 'https://es.wiktionary.org/wiki/Portada'],
      ['wt', 'Main_Page', 'en', 'https://en.wiktionary.org/wiki/Main_Page'],
      ['wikisource', 'Main_Page', 'en', 'https://en.wikisource.org/wiki/Main_Page'],
      ['wikisource', 'Portada', 'es', 'https://es.wikisource.org/wiki/Portada'],
      ['ws', 'Main_Page', 'en', 'https://en.wikisource.org/wiki/Main_Page'],
      ['wikinews', 'Main_Page', 'en', 'https://en.wikinews.org/wiki/Main_Page'],
      ['wikinews', 'Portada', 'es', 'https://es.wikinews.org/wiki/Portada'],
      ['wn', 'Main_Page', 'en', 'https://en.wikinews.org/wiki/Main_Page'],
      ['wikibooks', 'Main_Page', 'en', 'https://en.wikibooks.org/wiki/Main_Page'],
      ['wikibooks', 'Portada', 'es', 'https://es.wikibooks.org/wiki/Portada'],
      ['wb', 'Main_Page', 'en', 'https://en.wikibooks.org/wiki/Main_Page'],
    ];
  }

  /**
   * Get the plugin instance.
   *
   * @return \Drupal\freelinking\Plugin\freelinking\Wiki
   *   The wiki plugin.
   */
  protected function getPlugin() {
    $definition = [
      'id' => 'wiki',
      'title' => 'Wiki',
      'hidden' => FALSE,
      'weight' => 0,
      'settings' => [],
    ];
    return new Wiki([], 'wiki', $definition);
  }

}
