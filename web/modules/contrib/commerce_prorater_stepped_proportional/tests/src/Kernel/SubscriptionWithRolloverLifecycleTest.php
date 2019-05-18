<?php

namespace Drupal\Tests\commerce_prorater_stepped_proportional\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Tests\commerce_recurring\Kernel\RecurringKernelTestBase;

/**
 * Tests the subscription lifecycle with a rollover billing schedule.
 *
 * @group commerce_prorater_stepped_proportional
 */
class SubscriptionWithRolloverLifecycleTest extends RecurringKernelTestBase {

  /**
   * The recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'advancedqueue',
    'path',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_product',
    'commerce_recurring',
    'entity_reference_revisions',
    'commerce_prorater_stepped_proportional',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // An order item type that doesn't need a purchasable entity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    // Change the billing schedule's configuration.
    $this->billingSchedule->setBillingType(BillingSchedule::BILLING_TYPE_PREPAID);
    $this->billingSchedule->setPluginId('fixed_with_free_rollover');
    $this->billingSchedule->setPluginConfiguration([
      // Configure a 1 year schedule, whose final month is a rollover.
      'interval' => [
        'number' => '1',
        'unit' => 'year',
      ],
      'rollover_interval' => [
        'period' => 'month',
        'interval' => '1',
      ]
    ]);
    $this->billingSchedule->save();

    $this->recurringOrderManager = $this->container->get('commerce_recurring.order_manager');
  }

  /**
   * Tests the subscription lifecycle.
   */
  public function testSubscriptionLifecycle() {
    // Set time to the last month of a year.
    $this->rewindTime(strtotime('15 Dec 2017 19:00'));

    $first_order_item = OrderItem::create([
      'type' => 'test',
      'title' => 'I promise not to start a subscription',
      'unit_price' => [
        'number' => '10.00',
        'currency_code' => 'USD',
      ],
      'quantity' => 1,
    ]);
    $first_order_item->save();
    $second_order_item = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->variation,
      'unit_price' => [
        'number' => '2.00',
        'currency_code' => 'USD',
      ],
      'quantity' => '3',
    ]);
    $second_order_item->save();
    $initial_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [$first_order_item, $second_order_item],
      'state' => 'draft',
      'payment_method' => $this->paymentMethod,
    ]);
    $initial_order->save();

    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(0, $subscriptions);

    $initial_order->state = 'completed';
    $initial_order->save();

    $subscriptions = Subscription::loadMultiple();
    $this->assertCount(1, $subscriptions);
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = reset($subscriptions);

    $this->assertEquals($this->store->id(), $subscription->getStoreId());
    $this->assertEquals($this->billingSchedule->id(), $subscription->getBillingSchedule()->id());
    $this->assertEquals($this->user->id(), $subscription->getCustomerId());
    $this->assertEquals($this->paymentMethod->id(), $subscription->getPaymentMethod()->id());
    $this->assertEquals($this->variation->id(), $subscription->getPurchasedEntityId());
    $this->assertEquals($this->variation->getOrderItemTitle(), $subscription->getTitle());
    $this->assertEquals('3', $subscription->getQuantity());
    $this->assertEquals($this->variation->getPrice(), $subscription->getUnitPrice());
    $this->assertEquals('active', $subscription->getState()->value);

    // Confirm that a recurring order is present.
    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    $result = $order_storage->getQuery()
      ->condition('type', 'recurring')
      ->pager(1)
      ->execute();
    $this->assertNotEmpty($result);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load(reset($result));
    $this->assertNotEmpty($order);
    // Confirm that the recurring order has an order item for the subscription.
    $order_items = $order->getItems();
    $this->assertCount(1, $order_items);
    $order_item = reset($order_items);
    $this->assertEquals($subscription->id(), $order_item->get('subscription')->target_id);

    // The recurring order's billing period should be the next year.
    $recurring_order_billing_period = $order->billing_period->first()->toBillingPeriod();
    $this->assertContains('01 Jan 2018', $recurring_order_billing_period->getStartDate()->format(DATE_RSS));
    $this->assertContains('01 Jan 2019', $recurring_order_billing_period->getEndDate()->format(DATE_RSS));

    // The subscription starts at the time it was created.
    $subscription_starts = DrupalDateTime::createFromTimestamp($subscription->starts->value);
    $this->assertContains('15 Dec 2017', $subscription_starts->format(DATE_RSS));

    // Renew the order and the the billing period the next order has.
    $next_order = $this->recurringOrderManager->renewOrder($order);
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $next_billing_period_item */
    $next_order_billing_period = $next_order->get('billing_period')->first()->toBillingPeriod();

    $this->assertContains('01 Jan 2019', $next_order_billing_period->getStartDate()->format(DATE_RSS));
    $this->assertContains('01 Jan 2020', $next_order_billing_period->getEndDate()->format(DATE_RSS));
  }

}
