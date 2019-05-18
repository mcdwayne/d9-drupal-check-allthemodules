<?php

namespace Drupal\Tests\gopay\Unit\Payment;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\gopay\Contact\Contact;
use Drupal\gopay\Exception\GoPayInvalidSettingsException;
use Drupal\gopay\GoPayApi;
use Drupal\gopay\Item\Item;
use Drupal\Tests\UnitTestCase;
use Drupal\gopay\Payment\StandardPayment;
use GoPay\Definition\Payment\Currency;
use Drupal\Core\Config\ConfigFactory;
use GoPay\Definition\Payment\PaymentInstrument;

/**
 * @coversDefaultClass \Drupal\gopay\Payment\StandardPayment
 * @group gopay
 */
class StandardPaymentTest extends UnitTestCase {

  /**
   * ConfigFactoryMock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactoryMock;

  /**
   * GoPay Api.
   *
   * @var \Drupal\gopay\GoPayApiInterface
   */
  protected $goPayApiMock;

  /**
   * Setup Mocks.
   */
  public function setUp() {
    // Set config mock.
    $confingProphecy = $this->prophesize(ImmutableConfig::class);
    $confingProphecy->get('go_id')->willReturn('testgoid');
    $confingProphecy->get('return_callback')->willReturn('http://www.drupal.test.org/gopay/return');
    $confingProphecy->get('notification_callback')->willReturn('http://www.drupal.test.org/gopay/notification');
    $confingProphecy->get('default_payment_instrument')->willReturn(PaymentInstrument::BANK_ACCOUNT);
    $confingProphecy->get('allowed_payment_instruments')->willReturn([PaymentInstrument::BANK_ACCOUNT, PaymentInstrument::PAYMENT_CARD]);

    // Set config factory mock.
    $configFactoryProphecy = $this->prophesize(ConfigFactory::class);
    $configFactoryProphecy->get('gopay.settings')->willReturn($confingProphecy->reveal());
    $this->configFactoryMock = $configFactoryProphecy->reveal();

    // Set GoPayApi mock.
    $goPayApiProphecy = $this->prophesize(GoPayApi::class);
    $this->goPayApiMock = $goPayApiProphecy->reveal();
  }

  /**
   * Test default values.
   */
  public function testDefaultValues() {
    $expected_config = [
      'callback' => [
        'return_url' => 'http://www.drupal.test.org/gopay/return',
        'notification_url' => 'http://www.drupal.test.org/gopay/notification',
      ],
      'payer' => [
        'allowed_payment_instruments' => [PaymentInstrument::BANK_ACCOUNT, PaymentInstrument::PAYMENT_CARD],
        'default_payment_instrument' => PaymentInstrument::BANK_ACCOUNT,
      ],
      'target' => [
        'goid' => 'testgoid',
        'type' => 'ACCOUNT',
      ],
      'amount' => 100,
      'currency' => Currency::CZECH_CROWNS,
      'order_number' => NULL,
      'order_description' => NULL,
    ];

    $payment = new StandardPayment($this->configFactoryMock, $this->goPayApiMock);
    $payment->setAmount(100);
    $payment->setCurrency(Currency::CZECH_CROWNS);
    $payment_config = $payment->toArray();

    $this->assertArrayEquals($expected_config, $payment_config);
  }

  /**
   * Test setting of all values. In cascade style.
   */
  public function testAllSetters() {
    $expected_config = [
      'callback' => [
        'return_url' => 'http://myreturn.url',
        'notification_url' => 'http://mynotification.url',
      ],
      'payer' => [
        'allowed_payment_instruments' => [PaymentInstrument::BANK_ACCOUNT],
        'default_payment_instrument' => PaymentInstrument::PAYMENT_CARD,
        'contact' => [
          'first_name' => 'Alice',
          'last_name' => 'Wifeofbob',
          'country_code' => 'GER',
          'city' => 'Berlin',
          'street' => 'Strasse 1',
          'postal_code' => 789,
          'phone_number' => 1337,
          'email' => 'alice@berlin.de',
        ],
      ],
      'target' => [
        'goid' => 'testgoid',
        'type' => 'ACCOUNT',
      ],
      'amount' => 1000500,
      'currency' => Currency::BRITISH_POUND,
      'order_number' => 123,
      'order_description' => 'My order description',
      'items' => [
        [
          'name' => 'expensive product',
          'amount' => 1000000,
          'type' => 'ITEM',
          'product_url' => NULL,
          'ean' => NULL,
          'count' => 1,
          'vat_rate' => NULL,
        ],
        [
          'name' => 'cheap product',
          'amount' => 500,
          'type' => 'ITEM',
          'product_url' => NULL,
          'ean' => NULL,
          'count' => 5,
          'vat_rate' => NULL,
        ],
      ],
    ];

    $payment_config = (new StandardPayment($this->configFactoryMock, $this->goPayApiMock))
      ->setReturnUrl('http://myreturn.url')
      ->setNotificationUrl('http://mynotification.url')
      ->setAllowedPaymentInstruments([PaymentInstrument::BANK_ACCOUNT])
      ->setDefaultPaymentInstrument(PaymentInstrument::PAYMENT_CARD)
      ->setAmount(1000500)
      ->setCurrency(Currency::BRITISH_POUND)
      ->setOrderNumber(123)
      ->setOrderDescription('My order description')
      ->setContact((new Contact())
        ->setFirstName('Alice')
        ->setLastName('Wifeofbob')
        ->setCountryCode('GER')
        ->setCity('Berlin')
        ->setStreet('Strasse 1')
        ->setPostalCode(789)
        ->setPhoneNumber(1337)
        ->setEmail('alice@berlin.de')
      )
      ->addItem((new Item())
        ->setName('expensive product')
        ->setAmount(10000, FALSE)
      )
      ->addItem((new Item())
        ->setName('cheap product')
        ->setCount(5)
        ->setAmount(500)
      )
      ->toArray();

    $this->assertArrayEquals($expected_config, $payment_config);
  }

  /**
   * Test setting amount in units.
   */
  public function testAmountInUnits() {
    $expected_config = [
      'callback' => [
        'return_url' => 'http://www.drupal.test.org/gopay/return',
        'notification_url' => 'http://www.drupal.test.org/gopay/notification',
      ],
      'payer' => [
        'allowed_payment_instruments' => [PaymentInstrument::BANK_ACCOUNT, PaymentInstrument::PAYMENT_CARD],
        'default_payment_instrument' => PaymentInstrument::BANK_ACCOUNT,
      ],
      'target' => [
        'goid' => 'testgoid',
        'type' => 'ACCOUNT',
      ],
      'amount' => 2000,
      'currency' => Currency::CZECH_CROWNS,
      'order_number' => NULL,
      'order_description' => NULL,
    ];

    $payment = new StandardPayment($this->configFactoryMock, $this->goPayApiMock);
    $payment->setAmount(20, FALSE);
    $payment->setCurrency(Currency::CZECH_CROWNS);
    $payment_config = $payment->toArray();

    $this->assertArrayEquals($expected_config, $payment_config);
  }

  /**
   * Test missing amount property.
   */
  public function testMissingAmount() {
    $this->setExpectedException(GoPayInvalidSettingsException::class);
    $payment = new StandardPayment($this->configFactoryMock, $this->goPayApiMock);
    $payment->setCurrency(Currency::EUROS);
    $payment->toArray();
  }

  /**
   * Test missing currency property.
   */
  public function testMissingCurrency() {
    $this->setExpectedException(GoPayInvalidSettingsException::class);
    $payment = new StandardPayment($this->configFactoryMock, $this->goPayApiMock);
    $payment->setAmount(100);
    $payment->toArray();
  }

}
