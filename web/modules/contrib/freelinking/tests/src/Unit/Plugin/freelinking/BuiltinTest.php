<?php

namespace Drupal\Tests\freelinking\Unit\Plugin\freelinking;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\freelinking\Plugin\freelinking\Builtin;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the freelinking builtin plugin.
 *
 * @group freelinking
 */
class BuiltinTest extends UnitTestCase {

  /**
   * Translation Interface mock.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationInterfaceMock;

  /**
   * Plugin configuration.
   *
   * @var array
   */
  protected $configuration = ['settings' => []];

  /**
   * Plugin ID.
   *
   * @var string
   */
  protected $id = 'builtin';

  /**
   * Plugin definition.
   *
   * @var array
   */
  protected $definition = [
    'id' => 'builtin',
    'title' => 'Built-in',
    'weight' => -1,
    'hidden' => TRUE,
    'settings' => [],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Mock string translation service.
    $tProphet = $this->prophesize('\Drupal\Core\StringTranslation\TranslationInterface');
    $tProphet->translateString(Argument::any())->willReturn('Redact, show text only, or display the indicator');
    $this->translationInterfaceMock = $tProphet->reveal();

    $container = new ContainerBuilder();
    $container->set('string_translation', $tProphet->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Asserts that getTip returns the correct string.
   */
  public function testGetTip() {
    $accountProphet = $this->prophesize('\Drupal\Core\Session\AccountProxyInterface');
    $plugin = new Builtin($this->configuration, $this->id, $this->definition, $accountProphet->reveal());
    $this->assertEquals('Redact, show text only, or display the indicator', $plugin->getTip()->render());
  }

  /**
   * Asserts that getIndicator pattern is functional for all patterns.
   *
   * @param string $indicator
   *   The indicator to test.
   * @param int $expected
   *   The expected output from preg_match.
   *
   * @dataProvider getIndicatorProvider
   */
  public function testGetIndicator($indicator, $expected) {
    $accountProphet = $this->prophesize('\Drupal\Core\Session\AccountProxyInterface');
    $plugin = new Builtin($this->configuration, $this->id, $this->definition, $accountProphet->reveal());
    $this->assertEquals($expected, preg_match($plugin->getIndicator(), $indicator));
  }

  /**
   * Asserts that buildLink is functional for all patterns.
   *
   * @param string $indicator
   *   The indicator to test.
   * @param string $dest
   *   The target destination text.
   * @param bool $isAuthenticated
   *   The return value for isAuthenticated method.
   * @param string $text
   *   The expected text to find.
   *
   * @dataProvider buildLinkProvider
   */
  public function testBuildLink($indicator, $dest, $isAuthenticated, $text) {
    $accountProphet = $this->prophesize('\Drupal\Core\Session\AccountProxyInterface');
    $accountProphet->isAuthenticated()->willReturn($isAuthenticated);

    $target = [
      'indicator' => $indicator,
      'target' => '[[' . $indicator . '|' . $dest . ']]',
      'dest' => $dest,
      'text' => NULL,
    ];

    $expected = [
      '#markup' => $text,
    ];

    $plugin = new Builtin($this->configuration, $this->id, $this->definition, $accountProphet->reveal());

    $this->assertEquals($expected, $plugin->buildLink($target));
  }

  /**
   * Asserts that isHidden method returns TRUE.
   */
  public function testIsHidden() {
    $accountProphet = $this->prophesize('\Drupal\Core\Session\AccountProxyInterface');
    $plugin = new Builtin($this->configuration, $this->id, $this->definition, $accountProphet->reveal());
    $this->assertTrue($plugin->isHidden());
  }

  /**
   * Provides test parameters for ::testBuildLink().
   *
   * @return array
   *   An array of test parameters.
   */
  public function buildLinkProvider() {
    return [
      ['showtext', 'Show Text', FALSE, 'Show Text'],
      ['redact', 'Non Redacted', FALSE, '******'],
      ['redact', 'Non Redacted', TRUE, 'Non Redacted'],
      ['nowiki', 'No Wiki', FALSE, '[[No Wiki]]'],
    ];
  }

  /**
   * Provides test parameters for ::testGetIndicators().
   *
   * @return array
   *   An array of test parameters.
   */
  public function getIndicatorProvider() {
    return [
      ['nomatch', 0],
      ['showtext', 1],
      ['nowiki', 1],
      ['redact', 1],
    ];
  }

}
