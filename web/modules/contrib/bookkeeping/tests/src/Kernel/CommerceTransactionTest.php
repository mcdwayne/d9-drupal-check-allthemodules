<?php

namespace Drupal\Tests\bookkeeping\Kernel;

use Drupal\bookkeeping\Plugin\Field\FieldType\BookkeepingEntryItem;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Test commerce transaction integration.
 *
 * @group bookkeeping
 */
class CommerceTransactionTest extends CommerceKernelTestBase {

  use TransactionTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'bookkeeping',
    'dynamic_entity_reference',
    'entity_reference_revisions',
    'state_machine',
    'profile',
    'commerce_product',
    'commerce_order',
    'commerce_payment',
    'commerce_payment_example',
  ];

  /**
   * The payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   */
  protected $gateway;

  /**
   * The ongoing transaction count.
   *
   * @var int
   */
  protected $transactionCount = 0;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('bookkeeping_transaction');
    $this->installConfig('bookkeeping');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_payment');

    // Ensure the bookkeeping config is set up correctly.
    $this->container->get('bookkeeping.commerce_config')->initStore($this->store);

    // An order item type that doesn't need a purchasable entity for simplicity.
    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    // Add our gateway for payments.
    $this->gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
      'configuration' => [
        'api_key' => '2342fewfsfs',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $this->gateway->save();

    // Set our storage for the trait.
    $this->transactionStorage = $this->container
      ->get('entity_type.manager')
      ->getStorage('bookkeeping_transaction');
  }

  /**
   * Test a fairly standard order flow.
   */
  public function testOrderFlow() {
    // Create the order.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('10', 'USD'),
    ]);
    $order_item->save();

    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'order_items' => [$order_item],
      'state' => 'draft',
      'payment_gateway' => 'onsite',
    ]);
    $order->save();

    // Check that we have no transactions.
    $this->assertNewTransactionsCount(0, 'Transactions at order creation.');

    // Add another order item.
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('10', 'USD'),
    ]);
    $order_item->save();
    $order->addItem($order_item);
    $order->save();

    // Check that we still have no transactions.
    $this->assertNewTransactionsCount(0, 'Transactions after pre-complete change.');

    // Complete the order.
    /** @var \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface $state */
    $state = $order->getState();
    $state->applyTransitionById('place');
    $order->save();

    // Check that we have 1 transactions.
    $this->assertNewTransactionsCount(1, 'Transactions after completion.');
    $this->assertNewTransactionsDetail([
      [
        'generator' => 'commerce_order:payable',
        'entries' => [
          [
            'account' => 'commerce_store_' . $this->store->id(),
            'amount' => 20,
            'currency_code' => 'USD',
            'type' => BookkeepingEntryItem::TYPE_CREDIT,
          ],
          [
            'account' => 'accounts_receivable',
            'amount' => 20,
            'currency_code' => 'USD',
            'type' => BookkeepingEntryItem::TYPE_DEBIT,
          ],
        ],
      ],
    ], 'Transactions after completion');

    // Remove the second order item.
    $order->removeItem($order_item);
    $order->save();
    $order_item->delete();

    // Check that we have another transaction for the difference.
    $this->assertNewTransactionsCount(1, 'Transactions after post-completion change.');
    $this->assertNewTransactionsDetail([
      [
        'generator' => 'commerce_order:changed',
        'entries' => [
          [
            'account' => 'commerce_store_' . $this->store->id(),
            'amount' => 10,
            'currency_code' => 'USD',
            'type' => BookkeepingEntryItem::TYPE_DEBIT,
          ],
          [
            'account' => 'accounts_receivable',
            'amount' => 10,
            'currency_code' => 'USD',
            'type' => BookkeepingEntryItem::TYPE_CREDIT,
          ],
        ],
      ],
    ], 'Transactions after post-completion change');

    // Add a payment.
    $payment = Payment::create([
      'type' => 'payment_default',
      'payment_gateway' => $this->gateway->id(),
      'order_id' => $order->id(),
      'amount' => new Price(20, 'USD'),
      'state' => 'pending',
    ]);
    $payment->save();

    // Check that there is not a new transaction.
    $this->assertNewTransactionsCount(0, 'Transactions at payment creation.');

    // Complete the payment.
    $payment->setState('completed');
    $payment->save();

    // Check for the correct payment completion posting.
    $this->assertNewTransactionsCount(1, 'Transactions after payment completion.');
    $this->assertNewTransactionsDetail([
      [
        'generator' => 'commerce_payment:completed',
        'entries' => [
          [
            'account' => 'accounts_receivable',
            'amount' => 20,
            'currency_code' => 'USD',
            'type' => BookkeepingEntryItem::TYPE_CREDIT,
          ],
          [
            'account' => 'commerce_payment_gateway_' . $this->gateway->id(),
            'amount' => 20,
            'currency_code' => 'USD',
            'type' => BookkeepingEntryItem::TYPE_DEBIT,
          ],
        ],
      ],
    ], 'Transactions after payment completion');

    // Run a partial refund.
    $payment->setState('partially_refunded');
    $payment->setRefundedAmount(new Price(10, 'USD'));
    $payment->save();

    // Check for the correct payment refund posting.
    $this->assertNewTransactionsCount(1, 'Transactions after payment refund.');
    $this->assertNewTransactionsDetail([
      [
        'generator' => 'commerce_payment:changed',
        'entries' => [
          [
            'account' => 'accounts_receivable',
            'amount' => 10,
            'currency_code' => 'USD',
            'type' => BookkeepingEntryItem::TYPE_DEBIT,
          ],
          [
            'account' => 'commerce_payment_gateway_' . $this->gateway->id(),
            'amount' => 10,
            'currency_code' => 'USD',
            'type' => BookkeepingEntryItem::TYPE_CREDIT,
          ],
        ],
      ],
    ], 'Transactions after payment refund');
  }

}
