<?php

namespace Drupal\Tests\commerce_checkout_order_fields\Kernel;

use Drupal\commerce_checkout\Entity\CheckoutFlow;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Form\FormState;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the checkout pane.
 *
 * @group commerce_checkout_order_fields
 */
class CheckoutPaneTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_checkout',
    'commerce_checkout_order_fields',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_product');
    $this->installConfig('commerce_checkout');
    $this->installConfig('commerce_checkout_order_fields');
    $checkout_pane_manager = $this->container->get('plugin.manager.commerce_checkout_pane');
    $checkout_pane_manager->clearCachedDefinitions();
  }

  public function testOrderFieldsPanesDeriver() {
    $checkout_pane_manager = $this->container->get('plugin.manager.commerce_checkout_pane');
    $definitions = $checkout_pane_manager->getDefinitions();
    $this->assertTrue(isset($definitions['order_fields:checkout']));
  }

  /**
   * Tests the pane plugin.
   *
   * @dataProvider dataCheckoutPaneConfiguration
   */
  public function testCheckoutPaneConfiguration(array $pane_configuration, array $expected) {
    $checkout_flow = CheckoutFlow::load('default');
    $configuration = $checkout_flow->get('configuration');
    $configuration['panes']['order_fields:checkout'] = $pane_configuration;
    $checkout_flow->set('configuration', $configuration);
    // Save so we can verify the config schema.
    $checkout_flow->save();

    /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesInterface $plugin */
    $plugin = $checkout_flow->getPlugin();
    $panes = $plugin->getPanes();

    $this->assertTrue(isset($panes['order_fields:checkout']));
    $pane = $panes['order_fields:checkout'];
    $this->assertEquals($pane->getStepId(), $expected[0]);
    $this->assertEquals($expected[1], (string) $pane->buildConfigurationSummary());
    $this->assertEquals($expected[2], $pane->getWrapperElement());
  }

  /**
   * Verifies the coupons field widget is always removed.
   */
  public function testCheckoutCouponsRemoved() {
    $this->installModule('commerce_promotion');
    $checkout_flow = CheckoutFlow::load('default');
    /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesInterface $plugin */
    $plugin = $checkout_flow->getPlugin();
    $pane = $plugin->getPane('order_fields:checkout');

    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => 'text@example.com',
      'ip_address' => '127.0.0.1',
    ]);
    $pane->setOrder($order);
    $form_state = new FormState();
    $complete_form = [];
    $form = $pane->buildPaneForm([], $form_state, $complete_form);
    $this->assertFalse(isset($form['coupons']));
  }

  /**
   * Data generator for test.
   */
  public function dataCheckoutPaneConfiguration() {
    yield [
      [],
      [
        '_disabled',
        '<p>Wrapper element: Container</p><p>Display label: Order fields</p>',
        'container',
      ],
    ];
    yield [
      [
        'step' => 'order_information',
        'wrapper_element' => 'fieldset',
      ],
      [
        'order_information',
        '<p>Wrapper element: Fieldset</p><p>Display label: Order fields</p>',
        'fieldset',
      ],
    ];
    yield [
      [
        'step' => 'order_information',
        'wrapper_element' => 'container',
      ],
      [
        'order_information',
        '<p>Wrapper element: Container</p><p>Display label: Order fields</p>',
        'container',
      ],
    ];
    yield [
      [
        'step' => 'order_information',
        'wrapper_element' => 'fieldset',
        'display_label' => 'Custom fields',
      ],
      [
        'order_information',
        '<p>Wrapper element: Fieldset</p><p>Display label: Custom fields</p>',
        'fieldset',
      ],
    ];
  }

}
