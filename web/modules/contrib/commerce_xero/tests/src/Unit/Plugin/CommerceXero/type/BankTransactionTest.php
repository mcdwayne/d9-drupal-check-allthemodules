<?php

namespace Drupal\Tests\commerce_xero\Unit\Plugin\CommerceXero\type;

use Drupal\commerce_price\Price;
use Drupal\commerce_xero\Plugin\CommerceXero\type\BankTransaction;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\commerce_xero\Unit\CommerceXeroDataTestTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\xero\TypedData\Definition\LineItemDefinition;
use Drupal\xero\TypedData\Definition\TrackingCategoryOptionDefinition;
use Prophecy\Argument;

/**
 * Tests the bank transaction data type processor plugin.
 *
 * @group commerce_xero
 */
class BankTransactionTest extends UnitTestCase {

  use CommerceXeroDataTestTrait;

  /**
   * The plugin instance to test.
   *
   * @var \Drupal\commerce_xero\Plugin\CommerceXero\type\BankTransaction
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $urlGeneratorProphet = $this->prophesize('\Drupal\Core\Routing\UrlGeneratorInterface');
    $urlGeneratorProphet
      ->generateFromRoute('entity.commerce_order.canonical', Argument::any(), Argument::any(), FALSE)
      ->willReturn('http://example.com/admin/commerce/orders/1');

    $definition = [
      'id' => 'commerce_xero_bank_transaction',
      'label' => 'Bank Transaction',
      'type' => 'xero_bank_transaction',
      'settings' => [],
    ];
    $this->plugin = new BankTransaction($definition, 'commerce_xero_bank_transaction', $definition);

    $transaction = [
      'Type' => 'RECEIVE',
      'BankAccount' => [
        'Code' => '500',
      ],
      'Date' => '2018-08-10',
      'SubTotal' => '9.99',
      'Reference' => '',
      'LineItems' => [
        [
          'Description' => '9.99',
          'UnitAmount' => '9.99',
          'AccountCode' => '200',
        ],
      ],
    ];

    $this->createTypedDataProphet();
    $typedDataManager = $this->typedDataManagerProphet->reveal();
    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $typedDataManager);
    \Drupal::setContainer($container);

    $this->mockTypedData('list', [[]], 0, TrackingCategoryOptionDefinition::create('xero_tracking_option'));
    $this->mockTypedData('list', [[]], 0, LineItemDefinition::create('xero_line_item'));
    $this->mockTypedData('xero_bank_transaction', $transaction, NULL);

    $typedDataManager = $this->typedDataManagerProphet->reveal();
    $this->plugin->setTypedDataManager($typedDataManager);

    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $typedDataManager);
    $container->set('url_generator', $urlGeneratorProphet->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Asserts that bank transaction data is created.
   */
  public function testMake() {
    $customerProphet = $this->prophesize('\Drupal\user\UserInterface');
    $customerProphet->getAccountName()->willReturn('blah');

    $profileProphet = $this->prophesize('\Drupal\profile\Entity\ProfileInterface');
    $profileProphet->hasField('address')->willReturn(FALSE);

    $orderProphet = $this->prophesize('\Drupal\commerce_order\Entity\OrderInterface');
    $orderProphet->id()->willReturn(1);
    $orderProphet->getBillingProfile()->willReturn($profileProphet->reveal());
    $orderProphet->getCustomer()->willReturn($customerProphet->reveal());
    $orderProphet->getEmail()->willReturn('blah@example.com');

    $price = new Price('9.99', 'USD');
    $paymentEntityProphet = $this->prophesize('\Drupal\commerce_payment\Entity\PaymentInterface');
    $paymentEntityProphet->id()->willReturn(1);
    $paymentEntityProphet->label()->willReturn('9.99');
    $paymentEntityProphet->getAmount()->willReturn($price);
    $paymentEntityProphet->getRemoteId()->willReturn('');
    $paymentEntityProphet->getCompletedTime()->willReturn(date('U'));
    $paymentEntityProphet->getOrder()->willReturn($orderProphet->reveal());
    $paymentEntityProphet->isCompleted()->willReturn(TRUE);
    $strategyProphet = $this->prophesize('\Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface');
    $strategyProphet->get('bank_account')->willReturn('500');
    $strategyProphet->get('revenue_account')->willReturn('200');

    $transaction = $this->plugin->make($paymentEntityProphet->reveal(), $strategyProphet->reveal());
    $this->assertInstanceOf('\Drupal\xero\Plugin\DataType\BankTransaction', $transaction);
  }

}
