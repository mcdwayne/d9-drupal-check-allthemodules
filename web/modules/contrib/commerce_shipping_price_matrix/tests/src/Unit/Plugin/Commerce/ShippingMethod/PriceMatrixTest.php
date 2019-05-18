<?php

namespace Drupal\Tests\commerce_shipping_price_matrix\Unit\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping_price_matrix\Plugin\Commerce\ShippingMethod\PriceMatrix;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_shipping_price_matrix\Plugin\Commerce\ShippingMethod\PriceMatrix
 */
class PriceMatrixText extends UnitTestCase {

  /**
   * Provides data for testCalculateRates().
   *
   * @return array
   */
  public function providerConfiguration() {
    // We'll be testing cases that fall under all of the matrix entries,
    // including testing the minimum and maximum values.
    $matrix_values = [
      [
        'threshold' => '0',
        'type' => 'fixed_amount',
        'value' => '3',
      ],
      [
        'threshold' => '10',
        'type' => 'percentage',
        'value' => '0.5',
      ],
      [
        'threshold' => '20',
        'type' => 'percentage',
        'value' => '0.3',
        'min' => '7',
        'max' => '15',
      ],
    ];

    // order_subtotal configuration settings.
    $exclude_product_variations = ['excluded_product_variation_type'];
    $exclude_from_shipping_fields = ['field_exclude_from_shipping'];

    // Product purchased entity that should be included in the calculation.
    $mock_purchased_entity_1 = $this->prophesize(ProductVariationInterface::class);
    $mock_purchased_entity_1->getEntityTypeId()->willReturn('commerce_product_variation');
    $mock_purchased_entity_1->bundle()->willReturn('included_product_variation_type');
    $mock_purchased_entity_1->hasField($exclude_from_shipping_fields[0])->willReturn(FALSE);
    $purchased_entity_1 = $mock_purchased_entity_1->reveal();
    $purchased_entity_1_price = new Price('8', 'USD');
    // Product purchased entity that should not be included in the calculation
    // based on its variation type.
    $mock_purchased_entity_2 = $this->prophesize(ProductVariationInterface::class);
    $mock_purchased_entity_2->getEntityTypeId()->willReturn('commerce_product_variation');
    $mock_purchased_entity_2->bundle()->willReturn('excluded_product_variation_type');
    $mock_purchased_entity_2->hasField($exclude_from_shipping_fields[0])->willReturn(FALSE);
    $purchased_entity_2 = $mock_purchased_entity_2->reveal();
    $purchased_entity_2_price = new Price('8', 'USD');
    // Other purchased entity that should not be included in the calculation
    // based on either its variation type or the exclude-from-shipping field.
    $mock_purchased_entity_3 = $this->prophesize(ProductVariationInterface::class);
    $mock_purchased_entity_3->getEntityTypeId()->willReturn('commerce_product_variation');
    $mock_purchased_entity_3->bundle()->willReturn('excluded_product_variation_type');
    $mock_purchased_entity_3->hasField($exclude_from_shipping_fields[0])->willReturn(TRUE);
    $mock_field_item_list_3 = $this->prophesize(FieldItemListInterface::class);
    $mock_field_item_list_3->getValue()->willReturn([['value' => '1']]);
    $field_item_list_3 = $mock_field_item_list_3->reveal();
    $mock_purchased_entity_3->get($exclude_from_shipping_fields[0])->willReturn($field_item_list_3);
    $purchased_entity_3 = $mock_purchased_entity_3->reveal();
    $purchased_entity_3_price = new Price('8', 'USD');
    // Product purchased entity that should be included in the calculation,
    // together with $purchased_entity_1 match the minimum value of the $20
    // threshold.
    $mock_purchased_entity_4 = $this->prophesize(ProductVariationInterface::class);
    $mock_purchased_entity_4->getEntityTypeId()->willReturn('commerce_product_variation');
    $mock_purchased_entity_4->bundle()->willReturn('included_product_variation_type');
    $mock_purchased_entity_4->hasField($exclude_from_shipping_fields[0])->willReturn(FALSE);
    $purchased_entity_4 = $mock_purchased_entity_4->reveal();
    $purchased_entity_4_price = new Price('12', 'USD');
    // Product purchased entity that should be included in the calculation,
    // together with $purchased_entity_1 match the maximum value of the $20
    // threshold.
    $mock_purchased_entity_5 = $this->prophesize(ProductVariationInterface::class);
    $mock_purchased_entity_5->getEntityTypeId()->willReturn('commerce_product_variation');
    $mock_purchased_entity_5->bundle()->willReturn('included_product_variation_type');
    $mock_purchased_entity_5->hasField($exclude_from_shipping_fields[0])->willReturn(FALSE);
    $purchased_entity_5 = $mock_purchased_entity_5->reveal();
    $purchased_entity_5_price = new Price('100', 'USD');

    // Order items corresponding to the purchasable entities.
    $mock_order_item_1 = $this->prophesize(OrderItemInterface::class);
    $mock_order_item_1->getPurchasedEntity()->willReturn($purchased_entity_1);
    $mock_order_item_1->getTotalPrice()->willReturn($purchased_entity_1_price);
    $order_item_1 = $mock_order_item_1->reveal();
    $mock_order_item_2 = $this->prophesize(OrderItemInterface::class);
    $mock_order_item_2->getPurchasedEntity()->willReturn($purchased_entity_2);
    $mock_order_item_2->getTotalPrice()->willReturn($purchased_entity_2_price);
    $order_item_2 = $mock_order_item_2->reveal();
    $mock_order_item_3 = $this->prophesize(OrderItemInterface::class);
    $mock_order_item_3->getPurchasedEntity()->willReturn($purchased_entity_3);
    $mock_order_item_3->getTotalPrice()->willReturn($purchased_entity_3_price);
    $order_item_3 = $mock_order_item_3->reveal();
    $mock_order_item_4 = $this->prophesize(OrderItemInterface::class);
    $mock_order_item_4->getPurchasedEntity()->willReturn($purchased_entity_4);
    $mock_order_item_4->getTotalPrice()->willReturn($purchased_entity_4_price);
    $order_item_4 = $mock_order_item_4->reveal();
    $mock_order_item_5 = $this->prophesize(OrderItemInterface::class);
    $mock_order_item_5->getPurchasedEntity()->willReturn($purchased_entity_5);
    $mock_order_item_5->getTotalPrice()->willReturn($purchased_entity_5_price);
    $order_item_5 = $mock_order_item_5->reveal();

    // Order items that will be used for most of the tests.
    $order_items = [
      $order_item_1,
      $order_item_2,
      $order_item_3,
    ];

    // Test cases.
    return [
      // No product variations excluded, matrix entry 20 without min/max.
      [
        $matrix_values,
        NULL,
        NULL,
        $order_items,
        new Price('7.2', 'USD'),
      ],
      // Product variations excluded based on type, matrix entry 0.
      [
        $matrix_values,
        $exclude_product_variations,
        NULL,
        $order_items,
        new Price('3', 'USD'),
      ],
      // Product variations excluded based on field, matrix entry 10.
      [
        $matrix_values,
        NULL,
        $exclude_from_shipping_fields,
        $order_items,
        new Price('8', 'USD'),
      ],
      // Product variations excluded based on both type and field, matrix entry
      // 0.
      [
        $matrix_values,
        $exclude_product_variations,
        $exclude_from_shipping_fields,
        $order_items,
        new Price('3', 'USD'),
      ],
      // No product variations excluded, matrix entry 20 with minimum.
      [
        $matrix_values,
        NULL,
        NULL,
        [
          $order_item_1,
          $order_item_4,
        ],
        new Price('7', 'USD'),
      ],
      // No product variations excluded, matrix entry 20 with maximum.
      [
        $matrix_values,
        NULL,
        NULL,
        [
          $order_item_1,
          $order_item_5,
        ],
        new Price('15', 'USD'),
      ],
    ];
  }

  /**
   * Tests calculateRates().
   *
   * We could be testing the resolveMatrix function separately, but this tests
   * the whole thing.
   *
   * @covers ::calculateRates
   * @dataProvider providerConfiguration
   */
  public function testCalculateRates(
    $matrix_values,
    $exclude_product_variations,
    $exclude_from_shipping_fields,
    $order_items,
    $expected_cost
  ) {
    // Shipping method configuration.
    $configuration = [
      'price_matrix' => [
        'currency_code' => NULL,
        'values' => $matrix_values,
      ],
    ];
    $configuration['order_subtotal'] = [];
    if ($exclude_product_variations) {
      $configuration['order_subtotal']['exclude_product_variations'] = $exclude_product_variations;
    }
    if ($exclude_from_shipping_fields) {
      $configuration['order_subtotal']['exclude_from_shipping_fields'] = $exclude_from_shipping_fields;
    }
    $mock_package_type_manager = $this->getMockBuilder('Drupal\commerce_shipping\PackageTypeManagerInterface')
      ->getMock();

    // Create the shipping method.
    $price_matrix = new PriceMatrix(
      $configuration,
      '',
      ['services' => []],
      $mock_package_type_manager
    );

    // Calculate the order subtotal based on the given order items. We need it
    // for the getSubtotalPrice() stub function.
    $order_subtotal = new Price('0', 'USD');
    foreach ($order_items as $order_item) {
      $order_subtotal = $order_subtotal->add($order_item->getTotalPrice());
    }

    // Mock order and shipment objects.
    $mock_order = $this->prophesize(OrderInterface::class);
    $mock_order->getSubtotalPrice()->willReturn($order_subtotal);
    $mock_order->getItems()->willReturn($order_items);

    $mock_shipment = $this->prophesize(ShipmentInterface::class);
    $mock_shipment->getOrder()->willReturn($mock_order->reveal());

    // Calculate the shipping costs.
    $rates = $price_matrix->calculateRates($mock_shipment->reveal());

    // We should be returned only one rate with ID 0.
    $this->assertEquals(1, count($rates));

    $rate = $rates[0];
    $this->assertEquals(0, $rate->getId());

    // Ensure the returned amount is the expected one.
    $this->assertEquals(0, $rate->getAmount()->compareTo($expected_cost));
  }

}
