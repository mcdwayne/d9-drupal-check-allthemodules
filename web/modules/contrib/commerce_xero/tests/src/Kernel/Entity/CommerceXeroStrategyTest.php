<?php

namespace Drupal\Tests\commerce_xero\Kernel\Entity;

use Drupal\commerce_xero\Entity\CommerceXeroStrategy;
use Drupal\commerce_xero\Plugin\CommerceXero\processor\TrackingCategory;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests strategy entity methods.
 *
 * @group commerce_xero
 * @covers \Drupal\commerce_xero\Entity\CommerceXeroStrategy
 */
class CommerceXeroStrategyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce_xero',
    'serialization',
    'xero',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_xero_strategy');
  }

  /**
   * Asserts getting plugin information from entity.
   *
   * @param array $values
   *   Entity values.
   *
   * @dataProvider strategyValuesProvider
   */
  public function testGetEnabledPlugin(array $values) {
    $strategy = new CommerceXeroStrategy($values, 'commerce_xero_strategy');
    $this->assertEquals($values['plugins'][1], $strategy->getEnabledPlugin('commerce_xero_send'));
  }

  /**
   * Asserts getting plugin weight from entity.
   *
   * @param array $values
   *   Entity values.
   *
   * @dataProvider strategyValuesProvider
   */
  public function testGetPluginWeight(array $values) {
    $strategy = new CommerceXeroStrategy($values, 'commerce_xero_strategy');
    $queryProphet = $this->prophesize('\Drupal\xero\XeroQuery');

    $plugin = new TrackingCategory(
      [
        'settings' => ['tracking_category' => 'Region', 'tracking_option' => 'West Coast'],
      ],
      'commerce_xero_tracking_category',
      [
        'id' => 'commerce_xero_tracking_category',
        'label' => new TranslatableMarkup('Adds Tracking Category'),
        'types' => ['xero_transaction'],
        'execution' => 'immediate',
        'settings' => ['tracking_category' => '', 'tracking_option' => ''],
        'required' => FALSE,
      ],
      $queryProphet->reveal()
    );

    $this->assertEquals(0, $strategy->getPluginWeight($plugin));
  }

  /**
   * Asserts that config dependencies are created for plugins.
   *
   * @param array $values
   *   Entity values.
   *
   * @dataProvider strategyValuesProvider
   */
  public function testCalculateDependencies(array $values) {
    $queryProphet = $this->prophesize('\Drupal\xero\XeroQuery');
    $this->container->set('xero.query', $queryProphet->reveal());

    $strategy = new CommerceXeroStrategy($values, 'commerce_xero_strategy');

    $expected = [
      'config' => [
        'commerce_payment.commerce_payment_gateway.manual',
      ],
    ];
    $this->assertEquals($expected, $strategy->calculateDependencies());
  }

  /**
   * Gets test arguments for strategy entity.
   *
   * @return array
   *   An array of test arguments.
   */
  public function strategyValuesProvider() {
    $values = [
      'id' => $this->getRandomGenerator()->name(8, TRUE),
      'name' => $this->getRandomGenerator()->word(8),
      'status' => 1,
      'payment_gateway' => 'manual',
      'xero_type' => 'commerce_xero_bank_transaction',
      'bank_account' => '090',
      'revenue_account' => '400',
      'plugins' => [
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
      ],
    ];

    return [
      [$values],
    ];
  }

}
