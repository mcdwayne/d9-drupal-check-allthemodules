<?php

namespace Drupal\Tests\commerce_claim_gift_aid\Functional;

use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;

/**
 * Test whether gift aid works.
 *
 * We have to check if the field exists on the commerce_order entity.
 * We also have to check if an order can have gift aid populated against it.
 *
 * This is a functional test on whether this module has been enabled correctly.
 *
 * @group commerce_claim_gift_aid
 */
class CheckGiftAidTest extends OrderBrowserTestBase {

  /**
   * Module to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_claim_gift_aid',
  ];

  /**
   * Verify that we have the gift_aid field on the order entity.
   */
  public function testDoesGiftAidFieldExistOnOrderEntity() {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $fieldStorage */
    $fieldStorage = \Drupal::service('entity_field.manager');
    $commerceOrderBaseFields = $fieldStorage->getBaseFieldDefinitions(
      'commerce_order'
    );
    $this->assertTrue(in_array('gift_aid',
      array_keys($commerceOrderBaseFields))
    );
  }

  /**
   * Verify that we can record that an order has gift aid.
   */
  public function testGiftAidAgainstOrder() {
    $id = strtolower($this->randomMachineName(8));

    // Create an order item type that is eligible for gift aid.
    $this->createEntity('commerce_order_item_type', [
      'id' => $id,
      'label' => $this->randomMachineName(16),
      'purchasableEntityType' => NULL,
      'orderType' => 'default',
      'gift_aid' => TRUE,
    ]);

    // Create an order item.
    $orderItem = $this->createEntity('commerce_order_item', [
      'type' => $id,
      'unit_price' => [
        'number' => '123',
        'currency_code' => 'USD',
      ],
    ]);

    // Add this to the order.
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'order_items' => [$orderItem],
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
      'gift_aid' => TRUE,
    ]);

    $this->assertTrue($order->get('gift_aid')->value);
  }

}
