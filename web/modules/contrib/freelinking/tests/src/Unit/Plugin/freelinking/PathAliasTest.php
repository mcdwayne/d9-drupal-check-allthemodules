<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\freelinking\PathAlias;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the path_alias plugin.
 *
 * @group freelinking
 */
class PathAliasTest extends UnitTestCase {

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
    $tProphet->translateString(Argument::any())->willReturn('Click to view a local node.');
    $this->translationInterfaceMock = $tProphet->reveal();

    // Mock module handler service.
    $moduleHandlerProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandlerProphet->moduleExists('search')->willReturn(FALSE);

    // Path alias manager service.
    $aliasManagerProphet = $this->prophesize('\Drupal\Core\Path\AliasManagerInterface');
    $aliasManagerProphet->getPathByAlias(Argument::any(), Argument::any())->will(function ($args) {
      return $args[0] === '/validalias' ? '/node/1' : '/invalidalias';
    });

    // Path validator service.
    $pathValidatorProphet = $this->prophesize('\Drupal\Core\Path\PathValidatorInterface');

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->translationInterfaceMock);
    $this->container->set('module_handler', $moduleHandlerProphet->reveal());
    $this->container->set('path.alias_manager', $aliasManagerProphet->reveal());
    $this->container->set('path.validator', $pathValidatorProphet->reveal());
    \Drupal::setContainer($this->container);
  }

  /**
   * Get plugin instance.
   *
   * @param string $failoverOption
   *   The failover option.
   *
   * @return \Drupal\freelinking\Plugin\freelinking\PathAlias
   *   A plugin instance.
   */
  protected function getPlugin($failoverOption = 'search') {
    $configuration = ['settings' => ['failover' => $failoverOption]];
    $plugin_definition = [
      'id' => 'path_alias',
      'title' => 'Path Alias',
      'hidden' => FALSE,
      'weight' => 0,
    ] + $configuration;
    return PathAlias::create($this->container, $configuration, 'path_alias', $plugin_definition);
  }

  /**
   * Asserts that the indicator is functional.
   *
   * @param string $indicator
   *   The indicator string.
   * @param int $expected
   *   The expected value from preg_match.
   *
   * @dataProvider indicatorProvider
   */
  public function testGetIndicator($indicator, $expected) {
    $plugin = $this->getPlugin();
    $this->assertEquals($expected, preg_match($plugin->getIndicator(), $indicator));
  }

  /**
   * Asserts that getTip is functional.
   */
  public function testGetTip() {
    $plugin = $this->getPlugin();
    $this->assertEquals('Click to view a local node.', $plugin->getTip()->render());
  }

  /**
   * Asserts that defaultConfiguration provides the correct settings.
   */
  public function testDefaultConfiguration() {
    $plugin = $this->getPlugin();
    $this->assertArrayEquals(['settings' => ['failover' => 'search']], $plugin->defaultConfiguration());
  }

  /**
   * Asserts that buildLink is functional for valid and invalid path aliases.
   *
   * @param string $alias
   *   The alias string to test.
   * @param array $expected
   *   The expected render array without container dependencies.
   *
   * @dataProvider buildLinkProvider
   */
  public function testBuildLink($alias, array $expected) {
    $target = [
      'text' => 'A valid path alias',
      'dest' => $alias,
      'target' => 'alias:' . $alias . '|A valid path alias',
      'language' => NULL,
    ];
    $plugin = $this->getPlugin('error');

    if ($alias === 'validalias') {
      $expected['#url'] = Url::fromUri('base:node/1', ['language' => NULL]);
      $expected['#attributes'] = [
        'title' => new TranslatableMarkup(
          'Click to view a local node.',
          [],
          [],
          $this->translationInterfaceMock
        ),
      ];
    }
    else {
      $expected['#message'] = new TranslatableMarkup(
        'path “%path” not found',
        ['%path' => '/invalidalias'],
        [],
        $this->translationInterfaceMock
      );
    }

    $this->assertArrayEquals($expected, $plugin->buildLink($target));
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
      ['path', 1],
      ['alias', 1],
    ];
  }

  /**
   * Provider test parameters for ::testBuildLink.
   *
   * @return array
   *   An array of test parameters.
   */
  public function buildLinkProvider() {
    $validExpected = [
      '#type' => 'link',
      '#title' => 'A valid path alias',
    ];
    $invalidExpected = [
      '#theme' => 'freelink_error',
      '#plugin' => 'path_alias',
    ];
    return [
      ['validalias', $validExpected],
      ['invalidalias', $invalidExpected],
    ];
  }

}
