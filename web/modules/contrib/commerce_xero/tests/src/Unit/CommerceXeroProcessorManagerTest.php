<?php

namespace Drupal\Tests\commerce_xero\Unit;

use Drupal\commerce_xero\CommerceXeroProcessorManager;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Cache\NullBackend;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the commerce xero processor plugin manager.
 *
 * @group commerce_xero
 */
class CommerceXeroProcessorManagerTest extends UnitTestCase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\commerce_xero\CommerceXeroProcessorManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    FileCacheFactory::setPrefix(FileCacheFactory::DISABLE_CACHE);

    $moduleHandlerProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandlerProphet
      ->alter(
        'commerce_xero_process_process',
        Argument::type('\Drupal\Core\TypedData\ComplexDataInterface'),
        Argument::any());
    $moduleHandlerProphet
      ->alter(
        'commerce_xero_process',
        Argument::type('\Drupal\Core\TypedData\ComplexDataInterface'),
        Argument::any());
    $moduleHandler = $moduleHandlerProphet->reveal();
    $cacheBackend = new NullBackend('cache');

    $namespaces = new \ArrayIterator([
      '\Drupal\commerce_xero\Plugin\CommerceXero\processor' => [
        __DIR__ . '/../../../src/',
      ],
    ]);

    $this->manager = new CommerceXeroProcessorManager($namespaces, $cacheBackend, $moduleHandler);
  }

  /**
   * Asserts that the plugin manager exists.
   */
  public function testInitialize() {
    $this->assertInstanceOf('\Drupal\commerce_xero\CommerceXeroProcessorManager', $this->manager);
  }

  /**
   * Asserts that process method calls alter methods.
   */
  public function testProcess() {
    $strategyProphet = $this->prophesize('\Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface');
    $strategyProphet->get('plugins')->willReturn([]);
    $strategy = $strategyProphet->reveal();

    $paymentProphet = $this->prophesize('\Drupal\commerce_payment\Entity\PaymentInterface');
    $payment = $paymentProphet->reveal();

    $dataProphet = $this->prophesize('\Drupal\Core\TypedData\ComplexDataInterface');
    $list = $dataProphet->reveal();

    $result = $this->manager->process($strategy, $payment, $list, 'process');
    $this->assertEquals(TRUE, $result);
  }

  /**
   * Asserts that the process method runs.
   *
   * @param string $execution
   *   The execution state to pass into the method.
   * @param string[] $expected_plugins
   *   An array of expected plugin ids.
   *
   * @dataProvider executionProvider
   */
  public function testGetStrategyPluginCollection($execution, array $expected_plugins) {
    $strategyProphet = $this->prophesize('\Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface');
    $strategyProphet->get('plugins')->willReturn([
      [
        'name' => 'commerce_xero_tracking_category',
        'settings' => [
          'tracking_category' => 'Region',
          'tracking_option' => 'West Coast',
        ],
      ],
      [
        'name' => 'commerce_xero_send',
        'settings' => [],
      ],
    ]);
    $strategy = $strategyProphet->reveal();

    $plugins = $this->manager->getStrategyPluginCollection($strategy, $execution);

    $ids = array_keys($plugins->getInstanceIds());
    $this->assertEquals($expected_plugins, $ids);
  }

  /**
   * Gets test arguments for various execution states.
   *
   * @return array
   *   An array of test arguments.
   */
  public function executionProvider() {
    return [
      ['', ['commerce_xero_tracking_category', 'commerce_xero_send']],
      ['immediate', ['commerce_xero_tracking_category']],
      ['process', []],
      ['send', ['commerce_xero_send']],
    ];
  }

}
