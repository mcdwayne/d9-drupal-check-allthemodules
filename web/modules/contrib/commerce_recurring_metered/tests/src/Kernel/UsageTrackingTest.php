<?php

namespace Drupal\Tests\commerce_recurring_metered\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\commerce_recurring\Kernel\RecurringKernelTestBase;

/**
 * Tests usage tracking functionality, as well as the Counter and Gauge plugins.
 *
 * @group commerce_recurring_metered
 */
class UsageTrackingTest extends RecurringKernelTestBase {

  public static $modules = [
    'commerce_recurring_metered',
    'commerce_recurring_metered_test',
  ];

  /**
   * The recurring order manager.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * A product variation to be used for tracking counter usage.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $counterVariation;

  /**
   * A product variation to be used for tracking gauge usage.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $gaugeVariation;

  /**
   * A product variation that creates a subscription with usage tracking.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $usageSubscriptionVariation;

  /**
   * A prepaid billing schedule.
   *
   * @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface
   */
  protected $prepaidBillingSchedule;

  /**
   * A prepaid subscription that supports usage tracking.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $prepaidUsageSubscription;

  /**
   * A postpaid, rolling billing schedule.
   *
   * @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface
   */
  protected $postpaidRollingBillingSchedule;

  /**
   * A variation to start a postpaid, rolling subscription.
   *
   * @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface
   */
  protected $rollingUsageSubscriptionVariation;

  /**
   * Test usage tracking with the 'counter' plugin.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Exception
   */
  public function testCounterUsageTracking() {
    //
    //
    // COMMON
    //
    // .
    $counter_variation = ProductVariation::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '0.05',
        'currency_code' => 'USD',
      ],
    ]);
    $counter_variation->save();
    $this->counterVariation = $this->reloadEntity($counter_variation);

    $cv_free_quantity = ProductVariation::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'sku' => 'variation_300_free',
      'price' => [
        'number' => '0.05',
        'currency_code' => 'USD',
      ],
    ]);
    $cv_free_quantity->save();

    //
    //
    // POSTPAID
    //
    // .
    $added_subscription = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->usageSubscriptionVariation,
      'unit_price' => [
        'number' => '0.00',
        'currency_code' => 'USD',
      ],
      'quantity' => '1',
    ]);
    $added_subscription->save();
    $initial_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [$added_subscription],
      'state' => 'draft',
      'payment_method' => $this->paymentMethod,
    ]);
    $initial_order->save();

    $workflow = $initial_order->getState()->getWorkflow();
    $initial_order->getState()
      ->applyTransition($workflow->getTransition('place'));
    $initial_order->save();

    $subscriptions = Subscription::loadMultiple();

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = reset($subscriptions);

    $usage_proxy = $this->container->get('commerce_recurring_metered.usage_proxy');

    // Increase counter usage by 1.
    $usage_proxy->addUsage($subscription, $counter_variation);

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $subscription->getCurrentOrder();
    $this->recurringOrderManager->refreshOrder($order);

    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $field_billing_period */
    $field_billing_period = $order->get('billing_period')->first();
    $usage = $usage_proxy->getUsageForPeriod($subscription, $field_billing_period->toBillingPeriod());
    $latest_usage = end($usage['counter']);
    self::assertEquals($latest_usage->getQuantity(), 1, 'One counter usage was recorded.');

    $items = $order->getItems();
    self::assertCount(2, $items, 'Order has two items (subscription and added usage).');

    // Make it 300 total.
    $usage_proxy->addUsage($subscription, $counter_variation, 299);
    $this->recurringOrderManager->refreshOrder($order);

    $items = $order->getItems();
    self::assertCount(2, $items, 'Order still has two items (subscription and combined usage).');

    /** @var \Drupal\commerce_order\Entity\OrderItem $usage_item */
    $usage_item = end($items);
    self::assertEquals(new Price('15', 'USD'), $usage_item->getTotalPrice(), 'Usage total is 15 USD.');
    self::assertEquals(new Price('15', 'USD'), $order->getTotalPrice(), 'Order total is 15 USD.');

    // Add 301 of the counter variation with 300 free. This should increase the
    // price by 0.05.
    $this->container->get('commerce_recurring_metered.usage_proxy')->addUsage($subscription, $cv_free_quantity, 301);
    $this->recurringOrderManager->refreshOrder($order);
    self::assertEquals(new Price('15.05', 'USD'), $order->getTotalPrice(), 'Counter usage type: Free quantity works.');

    //
    //
    // POSTPAID (ROLLING)
    //
    // .
    $added_subscription = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->rollingUsageSubscriptionVariation,
      'unit_price' => [
        'number' => '0.00',
        'currency_code' => 'USD',
      ],
      'quantity' => '1',
    ]);
    $added_subscription->save();
    $initial_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [$added_subscription],
      'state' => 'draft',
      'payment_method' => $this->paymentMethod,
    ]);
    $initial_order->save();

    $workflow = $initial_order->getState()->getWorkflow();
    $initial_order->getState()
      ->applyTransition($workflow->getTransition('place'));
    $initial_order->save();

    $subscriptions = Subscription::loadMultiple();

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = end($subscriptions);

    // Increase counter usage by 1.
    $usage_proxy->addUsage($subscription, $counter_variation);

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $subscription->getCurrentOrder();
    $this->recurringOrderManager->refreshOrder($order);

    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $field_billing_period */
    $field_billing_period = $order->get('billing_period')->first();
    $usage = $usage_proxy->getUsageForPeriod($subscription, $field_billing_period->toBillingPeriod());
    $latest_usage = end($usage['counter']);
    self::assertEquals($latest_usage->getQuantity(), 1, 'Postpaid rolling: One counter usage was recorded.');

    $items = $order->getItems();
    self::assertCount(2, $items, 'Postpaid rolling: Order has two items (subscription and added usage).');

    // Make it 300 total.
    $usage_proxy->addUsage($subscription, $counter_variation, 299);
    $this->recurringOrderManager->refreshOrder($order);

    $items = $order->getItems();
    self::assertCount(2, $items, 'Postpaid rolling: Order still has two items (subscription and combined usage).');

    /** @var \Drupal\commerce_order\Entity\OrderItem $usage_item */
    $usage_item = end($items);
    self::assertEquals(new Price('15', 'USD'), $usage_item->getTotalPrice(), 'Usage total is 15 USD.');
    self::assertEquals(new Price('15', 'USD'), $order->getTotalPrice(), 'Order total is 15 USD.');

    // Add 299 of the counter variation with 300 free. This should change
    // nothing.
    $usage_proxy->addUsage($subscription, $cv_free_quantity, 299);
    $this->recurringOrderManager->refreshOrder($order);
    self::assertEquals(new Price('15', 'USD'), $order->getTotalPrice(), 'Postpaid rolling: Counter usage type: Usage below free quantity works.');

    // Now add 2 more for a total of 301. This should increase the price by
    // 0.05.
    $usage_proxy->addUsage($subscription, $cv_free_quantity, 2);
    $this->recurringOrderManager->refreshOrder($order);
    self::assertEquals(new Price('15.05', 'USD'), $order->getTotalPrice(), 'Postpaid rolling: Counter usage type: Usage above free quantity works.');

    //
    //
    // PREPAID
    //
    // .
    $added_prepaid_subscription = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->prepaidUsageSubscription,
      'unit_price' => [
        'number' => '5.00',
        'currency_code' => 'USD',
      ],
      'quantity' => '1',
    ]);
    $added_prepaid_subscription->save();
    $prepaid_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [$added_prepaid_subscription],
      'state' => 'draft',
      'payment_method' => $this->paymentMethod,
    ]);
    $prepaid_order->save();

    $prepaid_workflow = $prepaid_order->getState()->getWorkflow();
    $prepaid_order->getState()
      ->applyTransition($prepaid_workflow->getTransition('place'));
    $prepaid_order->save();

    $subscriptions = Subscription::loadMultiple();

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $prepaid_subscription */
    $prepaid_subscription = end($subscriptions);

    // Increase counter usage by 1.
    $usage_proxy->addUsage($prepaid_subscription, $counter_variation);

    /** @var \Drupal\commerce_order\Entity\Order $porder */
    $porder = $prepaid_subscription->getCurrentOrder();
    $this->recurringOrderManager->refreshOrder($porder);

    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $pfield_billing_period */
    $pfield_billing_period = $porder->get('billing_period')->first();
    $pusage = $usage_proxy->getUsageForPeriod($prepaid_subscription, $pfield_billing_period->toBillingPeriod());
    $platest_usage = end($pusage['counter']);
    self::assertEquals($platest_usage->getQuantity(), 1, 'One counter usage was recorded.');

    $items = $porder->getItems();
    self::assertCount(2, $items, 'Order has two items (subscription and added usage).');

    // Make it 300 total.
    $usage_proxy->addUsage($prepaid_subscription, $counter_variation, 299);
    $this->recurringOrderManager->refreshOrder($porder);

    $items = $porder->getItems();
    self::assertCount(2, $items, 'Order still has two items (subscription and combined usage).');

    /** @var \Drupal\commerce_order\Entity\OrderItem $usage_item */
    $usage_item = end($items);
    self::assertEquals($usage_item->getTotalPrice(), new Price('15', 'USD'), 'Usage total is 15 USD.');
    self::assertEquals(new Price('20', 'USD'), $porder->getTotalPrice(), 'Order total is 20 USD.');
  }

  /**
   * Test usage tracking with the 'gauge' plugin.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Exception
   */
  public function testGaugeUsageTracking() {
    //
    //
    // COMMON
    //
    // .
    $gauge_variation = ProductVariation::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '5',
        'currency_code' => 'USD',
      ],
    ]);
    $gauge_variation->save();
    $this->gaugeVariation = $this->reloadEntity($gauge_variation);

    $gv_free_quantity = ProductVariation::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'sku' => 'variation_5_free',
      'price' => [
        'number' => '5',
        'currency_code' => 'USD',
      ],
    ]);
    $gv_free_quantity->save();

    //
    //
    // POSTPAID
    //
    // .
    // For sanity, mock the current time as the start of the hour.
    $fake_now = new \DateTime();
    $fake_now->setTime($fake_now->format('G'), 0);
    $this->rewindTime($fake_now->format('U'));
    $added_subscription = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->usageSubscriptionVariation,
      'unit_price' => [
        'number' => '0.00',
        'currency_code' => 'USD',
      ],
      'quantity' => '1',
    ]);
    $added_subscription->save();
    $initial_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [$added_subscription],
      'state' => 'draft',
      'payment_method' => $this->paymentMethod,
    ]);
    $initial_order->save();

    $workflow = $initial_order->getState()->getWorkflow();
    $initial_order->getState()
      ->applyTransition($workflow->getTransition('place'));
    $initial_order->save();

    $subscriptions = Subscription::loadMultiple();

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = reset($subscriptions);
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $subscription->getCurrentOrder();
    $field_billing_period = $order->get('billing_period')->first();
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $field_billing_period */
    $order_period = $field_billing_period->toBillingPeriod();

    $usage_proxy = $this->container->get('commerce_recurring_metered.usage_proxy');

    // Increase gauge usage by 4. Add it for the entire billing period.
    $usage_proxy
      ->addUsage($subscription, $gauge_variation, 4, $order_period, 'gauge');

    $usage = $usage_proxy
      ->getUsageForPeriod($subscription, $order_period);
    $latest_usage = end($usage['gauge']);
    self::assertEquals($latest_usage->getQuantity(), 4, 'Four gauge usages were recorded.');

    $this->recurringOrderManager->refreshOrder($order);
    $items = $order->getItems();
    self::assertCount(2, $items, 'Order has two items (subscription and added usage).');

    // Check that overlaps of the same variation get resolved.
    // \DateTimePlus::add() was not adding, so converting back and forth.
    $halfway = $order_period->getStartDate()
      ->getPhpDateTime()
      ->add(new \DateInterval('PT30M'));
    $half_period = new BillingPeriod($order_period->getStartDate(), DrupalDateTime::createFromDateTime($halfway));
    $usage_proxy
      ->addUsage($subscription, $gauge_variation, 4, $half_period, 'gauge');
    $this->recurringOrderManager->refreshOrder($order);

    $items = $order->getItems();
    self::assertCount(3, $items, 'Order has three items (subscription and new usage).');
    $usage = $usage_proxy
      ->getUsageForPeriod($subscription, $order_period);
    $latest_usage = end($usage['gauge']);
    self::assertEquals($latest_usage->getQuantity(), 4, 'Four gauge usages were recorded.');

    /** @var \Drupal\commerce_order\Entity\OrderItem $usage_item */
    self::assertEquals(new Price('20', 'USD'), $order->getTotalPrice(), 'Order total is 20 USD.');

    // Test free quantity for gauge usage type.
    $usage_proxy->addUsage($subscription, $gv_free_quantity, 4, $order_period, 'gauge');
    $this->recurringOrderManager->refreshOrder($order);
    self::assertEquals(new Price('20', 'USD'), $order->getTotalPrice(), 'Gauge usage type: Usage under free quantity works.');

    $usage_proxy->addUsage($subscription, $gv_free_quantity, 7, $order_period, 'gauge');
    $this->recurringOrderManager->refreshOrder($order);
    // With free quantity applied, the second usage records comes out to two
    // usages at $5/period; $20 + $10 = $30.
    self::assertEquals(new Price('30', 'USD'), $order->getTotalPrice(), 'Gauge usage type: Usage above free quantity works.');

    // @codingStandardsIgnoreStart
    // @todo: Uncomment these tests once postpaid-fixed is fixed.
    // //
    // //
    // // POSTPAID (HALF BILLING PERIOD)
    // //
    // // .
    // // Mock the current time as the middle of the previous hour.
    // $fake_now = new \DateTime();
    // $fake_now->setTime((int) $fake_now->format('G') - 1, 30);
    // $this->rewindTime($fake_now->format('U'));
    // $added_subscription = OrderItem::create([
    //   'type' => 'default',
    //   'purchased_entity' => $this->usageSubscriptionVariation,
    //   'unit_price' => [
    //     'number' => '0.00',
    //     'currency_code' => 'USD',
    //   ],
    //   'quantity' => '1',
    // ]);
    // $added_subscription->save();
    // $initial_order = Order::create([
    //   'type' => 'default',
    //   'store_id' => $this->store,
    //   'uid' => $this->user,
    //   'order_items' => [$added_subscription],
    //   'state' => 'draft',
    //   'payment_method' => $this->paymentMethod,
    // ]);
    // $initial_order->save();
    //
    // $workflow = $initial_order->getState()->getWorkflow();
    // $initial_order->getState()
    //   ->applyTransition($workflow->getTransition('place'));
    // $initial_order->save();
    //
    // $subscriptions = Subscription::loadMultiple();
    //
    // /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    // $subscription = end($subscriptions);
    // /** @var \Drupal\commerce_order\Entity\Order $order */
    // $order = $subscription->getCurrentOrder();
    // $field_billing_period = $order->get('billing_period')->first();
    // /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $field_billing_period */
    // $order_period = $field_billing_period->toBillingPeriod();
    //
    // // Increase gauge usage by 4. Add it for the entire billing period.
    // $usage_proxy->addUsage($subscription, $gauge_variation, 4, $order_period, 'gauge');
    //
    // $usage = $usage_proxy->getUsageForPeriod($subscription, $order_period);
    // $latest_usage = end($usage['gauge']);
    // self::assertEquals($latest_usage->getQuantity(), 4, 'Partial billing period: Four gauge usages were recorded.');
    //
    // $this->recurringOrderManager->refreshOrder($order);
    // $items = $order->getItems();
    // self::assertCount(2, $items, 'Partial billing period: Order has two items (subscription and added usage).');
    //
    // // Check that overlaps of the same variation get resolved.
    // // \DateTimePlus::add() was not adding, so converting back and forth.
    // $halfway = $order_period->getStartDate()
    //   ->getPhpDateTime()
    //   ->add(new \DateInterval('PT30M'));
    // $half_period = new BillingPeriod($order_period->getStartDate(), DrupalDateTime::createFromDateTime($halfway));
    // $usage_proxy->addUsage($subscription, $gauge_variation, 4, $half_period, 'gauge');
    // $this->recurringOrderManager->refreshOrder($order);
    //
    // $items = $order->getItems();
    // // The partial usage was merged because the first billing period _is_ only
    // // 30 minutes.
    // self::assertCount(2, $items, 'Partial billing period: Order has two items (subscription and normalized usage).');
    // $usage = $usage_proxy
    //   ->getUsageForPeriod($subscription, $order_period);
    // $latest_usage = end($usage['gauge']);
    // self::assertEquals($latest_usage->getQuantity(), 4, 'Partial billing period: Four gauge usages were recorded.');
    //
    // /** @var \Drupal\commerce_order\Entity\OrderItem $usage_item */
    // self::assertEquals(new Price('10', 'USD'), $order->getTotalPrice(), 'Partial billing period: Order total is 20 USD.');
    //
    // // Test free quantity for gauge usage type.
    // $usage_proxy->addUsage($subscription, $gv_free_quantity, 7, $order_period, 'gauge');
    // $this->recurringOrderManager->refreshOrder($order);
    // // With free quantity applied, the second usage record comes out to two
    // // usages at $5/period; $20 + $10 = $30.
    // self::assertEquals(new Price('15', 'USD'), $order->getTotalPrice(), 'Partial billing period: Gauge usage type: Free quantity works.');
    // @codingStandardsIgnoreEnd

    //
    //
    // PREPAID
    //
    // .
    $added_prepaid_subscription = OrderItem::create([
      'type' => 'default',
      'purchased_entity' => $this->prepaidUsageSubscription,
      'unit_price' => [
        'number' => '5.00',
        'currency_code' => 'USD',
      ],
      'quantity' => '1',
    ]);
    $added_prepaid_subscription->save();
    $prepaid_order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->user,
      'order_items' => [$added_prepaid_subscription],
      'state' => 'draft',
      'payment_method' => $this->paymentMethod,
    ]);
    $prepaid_order->save();

    $prepaid_workflow = $prepaid_order->getState()->getWorkflow();
    $prepaid_order->getState()
      ->applyTransition($prepaid_workflow->getTransition('place'));
    $prepaid_order->save();

    $subscriptions = Subscription::loadMultiple();

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $prepaid_subscription */
    $prepaid_subscription = end($subscriptions);
    /** @var \Drupal\commerce_order\Entity\Order $porder */
    $porder = $prepaid_subscription->getCurrentOrder();

    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $pfield_billing_period */
    $pfield_billing_period = $porder->get('billing_period')->first();
    $porder_period = $pfield_billing_period->toBillingPeriod();

    // Increase gauge usage by 4. Add it for the entire billing period.
    $usage_proxy->addUsage($prepaid_subscription, $gauge_variation, 4, $porder_period, 'gauge');

    $pusage = $usage_proxy->getUsageForPeriod($prepaid_subscription, $porder_period);
    $platest_usage = end($pusage['gauge']);
    self::assertEquals($platest_usage->getQuantity(), 4, 'Four gauge usages were recorded.');

    $this->recurringOrderManager->refreshOrder($porder);
    $items = $porder->getItems();
    self::assertCount(2, $items, 'Order has two items (subscription and added usage).');

    // Check that overlaps of the same variation get resolved.
    // \DateTimePlus::add() was not adding, so converting back and forth.
    $halfway = $porder_period->getStartDate()
      ->getPhpDateTime()
      ->add(new \DateInterval('PT30M'));
    $half_period = new BillingPeriod($porder_period->getStartDate(), DrupalDateTime::createFromDateTime($halfway));
    $usage_proxy->addUsage($prepaid_subscription, $gauge_variation, 4, $half_period, 'gauge');
    $this->recurringOrderManager->refreshOrder($porder);

    $items = $porder->getItems();
    self::assertCount(3, $items, 'Order has three items (subscription, first usage, and extra added usage).');
    $usage = $usage_proxy->getUsageForPeriod($prepaid_subscription, $porder_period);
    $latest_usage = end($usage['gauge']);
    self::assertEquals($latest_usage->getQuantity(), 4, 'Four gauge usages were recorded.');

    /** @var \Drupal\commerce_order\Entity\OrderItem $usage_item */
    self::assertEquals(new Price('25', 'USD'), $porder->getTotalPrice(), 'Order total is 25 USD.');

    // Test free quantity for gauge usage type.
    $usage_proxy->addUsage($prepaid_subscription, $gv_free_quantity, 7, $porder_period, 'gauge');
    $this->recurringOrderManager->refreshOrder($porder);
    // With free quantity applied, the second usage records comes out to two
    // usages at $5/period; $20 + $10 = $30.
    self::assertEquals(new Price('35', 'USD'), $porder->getTotalPrice(), 'Gauge usage type: Free quantity works.');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('commerce_recurring_metered', 'commerce_recurring_usage');

    // Our tests don't care about trials for now.
    // @todo: Figure out if they should.
    $billing_schedule_config = $this->billingSchedule->getPluginConfiguration();
    unset($billing_schedule_config['trial_interval']);
    $this->billingSchedule->setPluginConfiguration($billing_schedule_config);
    $this->billingSchedule->save();

    $this->recurringOrderManager = $this->container->get('commerce_recurring.order_manager');

    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $postpaid_rolling_billing_schedule = BillingSchedule::create([
      'id' => 'rolling_test_id',
      'label' => 'Hourly schedule',
      'displayLabel' => 'Hourly schedule',
      'billingType' => BillingSchedule::BILLING_TYPE_POSTPAID,
      'plugin' => 'rolling',
      'configuration' => [
        'interval' => [
          'number' => '1',
          'unit' => 'hour',
        ],
      ],
    ]);
    $postpaid_rolling_billing_schedule->save();
    $this->postpaidRollingBillingSchedule = $this->reloadEntity($postpaid_rolling_billing_schedule);

    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $prepaid_billing_schedule */
    $prepaid_billing_schedule = BillingSchedule::create([
      'id' => 'prepaid_test_id',
      'label' => 'Hourly schedule',
      'displayLabel' => 'Hourly schedule',
      'billingType' => BillingSchedule::BILLING_TYPE_PREPAID,
      'plugin' => 'rolling',
      'configuration' => [
        'interval' => [
          'number' => '1',
          'unit' => 'hour',
        ],
      ],
    ]);
    $prepaid_billing_schedule->save();
    $this->prepaidBillingSchedule = $this->reloadEntity($prepaid_billing_schedule);

    // Postpaid subscription variation.
    $usage_subscription_variation = ProductVariation::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '0.00',
        'currency_code' => 'USD',
      ],
      'billing_schedule' => $this->billingSchedule,
      'subscription_type' => [
        'target_plugin_id' => 'usage_test_product_variation',
      ],
    ]);
    $usage_subscription_variation->save();
    $this->usageSubscriptionVariation = $this->reloadEntity($usage_subscription_variation);

    // Postpaid (rolling) subscription variation.
    $postpaid_usage_subscription_variation = ProductVariation::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '0.00',
        'currency_code' => 'USD',
      ],
      'billing_schedule' => $this->postpaidRollingBillingSchedule,
      'subscription_type' => [
        'target_plugin_id' => 'usage_test_product_variation',
      ],
    ]);
    $postpaid_usage_subscription_variation->save();
    $this->rollingUsageSubscriptionVariation = $this->reloadEntity($postpaid_usage_subscription_variation);

    // Prepaid subscription variation.
    $prepaid_subscription = ProductVariation::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '5.00',
        'currency_code' => 'USD',
      ],
      'billing_schedule' => $this->prepaidBillingSchedule,
      'subscription_type' => [
        'target_plugin_id' => 'usage_test_product_variation',
      ],
    ]);
    $prepaid_subscription->save();
    $this->prepaidUsageSubscription = $this->reloadEntity($prepaid_subscription);
  }

}
