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
class PathAliasSearchTest extends UnitTestCase {

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
    $moduleHandlerProphet->moduleExists('search')->willReturn(TRUE);

    // Path alias manager service.
    $aliasManagerProphet = $this->prophesize('\Drupal\Core\Path\AliasManagerInterface');
    $aliasManagerProphet->getPathByAlias('/invalidalias', Argument::any())->willReturn('/invalidalias');

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
   * Asserts that buildLink is functional for search failover.
   */
  public function testBuildLink() {
    $target = [
      'text' => 'A valid path alias',
      'dest' => 'invalidalias',
      'target' => 'alias:invalidalias|A valid path alias',
      'language' => NULL,
    ];
    $plugin = $this->getPlugin('search');

    $expected = [
      '#type' => 'link',
      '#title' => 'A valid path alias',
      '#url' => Url::fromUri(
        'base:search',
        ['query' => ['keys' => '/invalidalias'], 'language' => NULL]
      ),
      '#attributes' => [
        'title' => new TranslatableMarkup(
          'Click to view a local node.',
          [],
          [],
          $this->translationInterfaceMock
        ),
      ],
    ];

    $this->assertArrayEquals($expected, $plugin->buildLink($target));
  }

}
