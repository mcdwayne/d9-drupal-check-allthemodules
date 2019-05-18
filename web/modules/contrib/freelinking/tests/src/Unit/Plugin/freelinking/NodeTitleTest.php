<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\freelinking\NodeTitle;
use Prophecy\Argument;

/**
 * Tests the nodetitle plugin behavior.
 *
 * @group freelinking
 */
class NodeTitleTest extends NodeTestBase {

  /**
   * Freelinking plugin.
   *
   * @var \Drupal\freelinking\Plugin\FreelinkingPluginInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    // Mock the translation service.
    $tProphet = $this->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $tProphet
      ->translateString(Argument::any())
      ->willReturn('Click to view a local node');

    // Mock Entity Type Manager.
    $entityManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');

    // Mock Entity Query via Mock Builder to support chaining.
    $entityQuery = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityQuery->expects($this->any())->method('condition')->willReturnSelf();
    $entityQuery->expects($this->any())->method('accessCheck')->willReturnSelf();
    $entityQuery->expects($this->any())->method('execute')->willReturn([1 => 1, 2 => 2]);

    // Mock Node Storage.
    $nodeStorageProphet = $this->prophesize('\Drupal\node\NodeStorageInterface');
    $nodeStorageProphet->getQuery('AND')->willReturn($entityQuery);

    // Mock Entity Type Manager getStorage.
    $entityManagerProphet->getStorage('node')->wilLReturn($nodeStorageProphet->reveal());

    // Mock Module Handler.
    $moduleHandlerProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');

    $container = new ContainerBuilder();
    $container->set('string_translation', $tProphet->reveal());
    $container->set('entity_type.manager', $entityManagerProphet->reveal());
    $container->set('module_handler', $moduleHandlerProphet->reveal());

    \Drupal::setContainer($container);

    $this->plugin = NodeTitle::create(
      $container,
      [
        'settings' => ['nodetypes' => [], 'failover' => ''],
      ],
      'nodetitle',
      []
    );
  }

  /**
   * Assert that getTip returns TranslatableMarkup.
   */
  public function testGetTip() {
    $this->assertEquals('Click to view a local node', $this->plugin->getTip()->render());
  }

  /**
   * Assert that getIndicator is a pattern.
   *
   * @param string $test
   *   The string to test the pattern against.
   * @param int $expected
   *   The expected return from preg_match().
   *
   * @dataProvider indicatorProvider
   */
  public function testGetIndicator($test, $expected) {
    $this->assertEquals($expected, preg_match($this->plugin->getIndicator(), $test));
  }

  /**
   * Assert the default configuration.
   */
  public function testDefaultConfiguration() {
    $this->assertArrayEquals(
      ['settings' => ['nodetypes' => [], 'failover' => '']],
      $this->plugin->defaultConfiguration()
    );
  }

  /**
   * Assert that build link will return a render array.
   */
  public function testBuildLink() {
    $language = self::getDefaultLanguage();
    $expected = [
      '#type' => 'link',
      '#title' => 'Test Node',
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => 1], ['language' => $language]),
      '#attributes' => [
        'title' => $this->plugin->getTip(),
      ],
    ];
    $target = ['dest' => 'Test Node', 'language' => $language];
    $this->assertArrayEquals($expected, $this->plugin->buildLink($target));
  }

  /**
   * Provide strings to test indicator pattern with expected result.
   *
   * @return array
   *   An array of test method arguments.
   */
  public function indicatorProvider() {
    return [
      ['ntnomatch', 0],
      ['nt', 1],
      ['nodetitle', 1],
      ['title', 1],
    ];
  }

}
