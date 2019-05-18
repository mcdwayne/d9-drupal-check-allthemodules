<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\freelinking\Search;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the failover options for search plugin.
 *
 * @group freelinking
 */
class SearchFailoverTest extends UnitTestCase {

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

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
    $moduleHandlerProphet->moduleExists('search')->willReturn(FALSE);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->translationInterfaceMock);
    $container->set('module_handler', $moduleHandlerProphet->reveal());
    $container->set('path.validator', $pathValidatorProphet->reveal());
    \Drupal::setContainer($container);

    $this->container = $container;
  }

  /**
   * Asserts the failover functionality for the search plugin.
   *
   * @param string $failoverOption
   *   The failover option to test.
   * @param array $expected
   *   The expected render array without any items depending on the container.
   *
   * @dataProvider buildLinkProvider
   */
  public function testBuildLink($failoverOption, array $expected) {

    $target = [
      'target' => 'search:Test Search|Test Search',
      'dest' => 'Test Search',
      'text' => 'Test Search',
      'language' => NULL,
    ];

    // Build out the expected array depending on the failover option.
    if ($failoverOption === 'google') {
      $expected['#url'] = Url::fromUri(
        'https://google.com/search',
        [
          'query' => ['q' => 'Test+Search', 'hl' => 'en'],
          'language' => NULL,
          'absolute' => TRUE,
        ]
      );
    }
    elseif ($failoverOption === 'error') {
      $expected['#message'] = new TranslatableMarkup(
        'Search unavailable',
        [],
        [],
        $this->translationInterfaceMock
      );
    }

    $plugin_definition = [
      'id' => 'search',
      'title' => 'Search',
      'hidden' => FALSE,
      'weight' => 0,
      'settings' => ['failover' => $failoverOption],
    ];
    $configuration = ['settings' => ['failover' => $failoverOption]];
    $plugin = Search::create($this->container, $configuration, 'search', $plugin_definition);


    $this->assertArrayEquals($expected, $plugin->buildLink($target));
  }

  /**
   * Provide test parameters for ::testBuildLink.
   *
   * @return array
   *   An array of test parameters.
   */
  public function buildLinkProvider() {
    $errorExpected = [
      '#theme' => 'freelink_error',
      '#plugin' => 'search',
      '#message' => 'Search unavailable',
    ];
    $googleExpected = [
      '#type' => 'link',
      '#title' => 'Google Search Test Search',
      '#attributes' => [
        'title' => 'Search this site for content like “%dest”.',
      ],
    ];

    return [
      ['error', $errorExpected],
      ['google', $googleExpected],
    ];
  }

}
