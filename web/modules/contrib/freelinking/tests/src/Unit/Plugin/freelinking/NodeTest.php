<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\freelinking\Node;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the nid plugin.
 *
 * @group freelinking
 */
class NodeTest extends NodeTestBase {

  /**
   * The translation interface.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationInterfaceMock;

  /**
   * The plugin to test.
   *
   * @var \Drupal\freelinking\Plugin\freelinking\Node
   */
  protected $plugin;

  /**
   * A mock container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Mock string translation interface.
    $tProphet = $this->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $tProphet->translateString(Argument::any())->willReturn('Click to view a local node.');
    $this->translationInterfaceMock = $tProphet->reveal();

    // Mock node entity.
    $nodeProphet = $this->prophesize('\Drupal\node\Entity\Node');
    $nodeProphet->id()->willReturn(1);
    $nodeProphet->label()->willReturn('Test Node');
    $nodeProphet->language()->willReturn(self::getDefaultLanguage());

    // Mock node storage interface.
    $nodeStorageProphet = $this->prophesize('\Drupal\node\NodeStorageInterface');
    $nodeStorageProphet->load(1)->willReturn($nodeProphet->reveal());
    $nodeStorageProphet->load(2)->willReturn(NULL);

    // Mock entity type manager.
    $entityManagerProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entityManagerProphet->getStorage('node')->willReturn($nodeStorageProphet->reveal());

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityManagerProphet->reveal());
    $container->set('string_translation', $this->translationInterfaceMock);
    \Drupal::setContainer($container);

    $this->container = $container;

    $plugin_definition = [
      'id' => 'nid',
      'title' => 'Node ID',
      'hidden' => FALSE,
      'weight' => 0,
      'settings' => [],
    ];
    $this->plugin = Node::create($container, [], 'nid', $plugin_definition);
  }

  /**
   * Assert that getIndicator method returns correct value.
   *
   * @param string $indicator
   *   The indicator string to test.
   * @param int $expected
   *   The expected result from preg_match().
   *
   * @dataProvider indicatorProvider
   */
  public function testGetIndicator($indicator, $expected) {
    $this->assertEquals($expected, preg_match($this->plugin->getIndicator(), $indicator));
  }

  /**
   * Assert the getTip method returns correct value.
   */
  public function testGetTip() {
    $this->assertEquals('Click to view a local node.', $this->plugin->getTip()->render());
  }

  /**
   * Assert the buildLink method returns render array.
   *
   * @param array $target
   *   The target array.
   * @param array $expected
   *   The expected render array.
   * @param bool $shouldFailover
   *   Test logic to place container-dependent render array items.
   *
   * @dataProvider buildLinkProvider
   */
  public function testBuildLink(array $target, array $expected, $shouldFailover = FALSE) {
    $language = self::getDefaultLanguage();

    if ($shouldFailover) {
      $expected['#message'] = new TranslatableMarkup(
        $expected['#message'],
        ['@nid' => '2'],
        [],
        $this->translationInterfaceMock
      );
    }
    else {
      $expected['#url'] = Url::fromRoute('entity.node.canonical', ['node' => 1], ['language' => $language]);
      $expected['#attributes']['title'] = new TranslatableMarkup(
        'Click to view a local node',
        [],
        [],
        $this->translationInterfaceMock
      );
    }

    $this->assertArrayEquals($expected, $this->plugin->buildLink($target));
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
      ['n', 1],
      ['nid', 1],
      ['node', 1],
    ];
  }

  /**
   * Provide test parameters for ::testBuildLink.
   *
   * @return array
   *   An array of test parameters.
   */
  public function buildLinkProvider() {
    $language = self::getDefaultLanguage();
    $failoverTarget = [
      'target' => 'nid:2',
      'dest' => '2',
      'language' => $language,
    ];
    $failoverExpected = [
      '#theme' => 'freelink_error',
      '#plugin' => 'nid',
      '#message' => 'Invalid node ID @nid',
    ];
    $successTarget = [
      'target' => 'nid:1',
      'dest' => '1',
      'language' => $language,
    ];
    $successExpected = [
      '#type' => 'link',
      '#title' => 'Test Node',
      '#attributes' => [
        'title' => 'Click to view a local node',
      ],
    ];

    return [
      [$failoverTarget, $failoverExpected, TRUE],
      [$successTarget, $successExpected, FALSE],
    ];
  }

}
