<?php

namespace Drupal\Tests\commerce_xero\Unit;

use Drupal\commerce_price\Price;
use Drupal\commerce_xero\CommerceXeroDataTypeManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\xero\TypedData\Definition\LineItemDefinition;
use Drupal\xero\TypedData\Definition\TrackingCategoryOptionDefinition;
use Prophecy\Argument;

/**
 * Tests the commerce xero processor data type manager.
 *
 * @group commerce_xero
 */
class CommerceXeroDataTypeManagerTest extends UnitTestCase {

  use CommerceXeroDataTestTrait;

  /**
   * The plugin manager instance.
   *
   * @var \Drupal\commerce_xero\CommerceXeroDataTypeManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $moduleHandlerProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandler = $moduleHandlerProphet->reveal();
    $namespaces = new \ArrayIterator();

    $cacheMock = (object) [
      'id' => 'commerce_xero_data_type_plugins',
      'data' => [
        'commerce_xero_bank_transaction' => [
          'id' => 'commerce_xero_bank_transaction',
          'label' => 'Bank Transaction',
          'type' => 'xero_bank_transaction',
          'settings' => [],
          'class' => '\Drupal\commerce_xero\Plugin\CommerceXero\type\BankTransaction',
        ],
      ],
    ];

    $cacheProphet = $this->prophesize('\Drupal\Core\Cache\CacheBackendInterface');
    $cacheProphet
      ->get('commerce_xero_data_type_plugins')
      ->willReturn($cacheMock);

    $urlGeneratorProphet = $this->prophesize('\Drupal\Core\Routing\UrlGeneratorInterface');
    $urlGeneratorProphet
      ->generateFromRoute('entity.commerce_order.canonical', Argument::any(), Argument::any(), FALSE)
      ->willReturn('http://example.com/admin/commerce/orders/1');

    $this->manager = new CommerceXeroDataTypeManager($namespaces, $cacheProphet->reveal(), $moduleHandler);

    $this->createTypedDataProphet();

    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $this->typedDataManagerProphet->reveal());
    \Drupal::setContainer($container);

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

    $this->mockTypedData('list', [[]], 0, TrackingCategoryOptionDefinition::create('xero_tracking_option'));
    $this->mockTypedData('list', [[]], 0, LineItemDefinition::create('xero_line_item'));
    $this->mockTypedData('xero_bank_transaction', $transaction);

    // Sets the container again because.
    $container->set('typed_data_manager', $this->typedDataManagerProphet->reveal());
    $container->set('url_generator', $urlGeneratorProphet->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Asserts that the plugin manager exists.
   */
  public function testInitialize() {
    $this->assertInstanceOf('\Drupal\commerce_xero\CommerceXeroDataTypeManager', $this->manager);
  }

  /**
   * Asserts that the plugin manager can create a data type.
   */
  public function testCreateData() {
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
    $paymentEntityProphet->isCompleted()->willreturn(TRUE);
    $strategyProphet = $this->prophesize('\Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface');
    $strategyProphet->get('bank_account')->willReturn('500');
    $strategyProphet->get('revenue_account')->willReturn('200');
    $strategyProphet->get('xero_type')->willReturn('commerce_xero_bank_transaction');

    $data = $this->manager->createData($paymentEntityProphet->reveal(), $strategyProphet->reveal());

    $this->assertInstanceOf('\Drupal\xero\Plugin\DataType\BankTransaction', $data);
  }

}
