<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\freelinking\Plugin\freelinking\NodeTitle;
use Prophecy\Argument;

/**
 * Tests the failover options for nodetitle plugin.
 *
 * @group freelinking
 */
class NodeTitleFailoverTest extends NodeTestBase {

  /**
   * Mock container.
   *
   * @var \Drupal\Core\DependencyInjection\Container
   */
  protected $container;

  /**
   * Tranlation interface mock.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationInterfaceMock;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    // Mock the translation service.
    $tProphet = $this->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $tProphet
      ->translateString(Argument::any())
      ->willReturn('Click to view a local node');
    $this->translationInterfaceMock = $tProphet->reveal();

    // Mock Entity Type Manager.
    $entityManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');

    // Mock Entity Query via Mock Builder to support chaining.
    $entityQuery = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityQuery->expects($this->any())->method('condition')->willReturnSelf();
    $entityQuery->expects($this->any())
      ->method('accessCheck')
      ->willReturnSelf();
    $entityQuery->expects($this->any())->method('execute')->willReturn([]);

    // Mock Node Storage.
    $nodeStorageProphet = $this->prophesize('\Drupal\node\NodeStorageInterface');
    $nodeStorageProphet->getQuery('AND')->willReturn($entityQuery);

    // Mock Entity Type Manager getStorage.
    $entityManagerProphet->getStorage('node')->wilLReturn($nodeStorageProphet->reveal());

    // Mock Module Handler.
    $moduleHandlerProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandlerProphet->moduleExists('search')->willReturn(TRUE);
    $moduleHandlerProphet->moduleExists('freelinking_prepopulate')->willReturn(TRUE);

    // Mock Path Validator.
    $pathValidatorProphet = $this->prophesize('\Drupal\Core\Path\PathValidatorInterface');

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->translationInterfaceMock);
    $container->set('entity_type.manager', $entityManagerProphet->reveal());
    $container->set('path.validator', $pathValidatorProphet->reveal());
    $container->set('module_handler', $moduleHandlerProphet->reveal());

    \Drupal::setContainer($container);

    $this->container = $container;
  }

  /**
   * Assert that failover option displays correctly.
   *
   * @param string $failoverOption
   *   The failover option to test.
   * @param array $expected
   *   The expected render array.
   *
   * @dataProvider failoverProvider
   */
  public function testFailover($failoverOption, array $expected) {
    $plugin = NodeTitle::create(
      $this->container,
      [
        'settings' => ['nodetypes' => [], 'failover' => $failoverOption],
      ],
      'nodetitle',
      []
    );

    // Mock the parsed target array.
    $target = [
      'dest' => 'Test Node',
      'language' => $this->getDefaultLanguage(),
      'target' => 'Test Node|Test Node',
    ];

    // Populate #url for the search test because container.
    if ($failoverOption === 'error') {
      $expected['#message'] = new TranslatableMarkup(
        'Node title %target does not exist',
        ['%target' => 'Test Node'],
        [],
        $this->translationInterfaceMock
      );
    }

    $link = $plugin->buildLink($target);
    $this->assertArrayEquals($expected, $link);
  }

  /**
   * Provide test parameters for ::testFailover.
   *
   * @return array
   *   An array of options and expected values.
   */
  public function failoverProvider() {
    $noneExpected = [
      '#markup' => '[[nodetitle:Test Node|Test Node]]',
    ];
    $showTextExpected = [
      'error' => 'showtext',
    ];
    $searchExpected = [
      'error' => 'search',
    ];
    $prepopulateExpected = [
      'error' => 'prepopulate',
    ];
    $errorExpected = [
      '#theme' => 'freelink_error',
      '#plugin' => 'nodetitle',
    ];

    return [
      ['_none', $noneExpected],
      ['showtext', $showTextExpected],
      ['search', $searchExpected],
      ['prepopulate', $prepopulateExpected],
      ['error', $errorExpected],
    ];
  }

}
