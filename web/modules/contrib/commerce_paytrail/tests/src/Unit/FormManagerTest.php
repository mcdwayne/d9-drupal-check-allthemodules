<?php

namespace Drupal\Tests\commerce_paytrail\Unit;

use Drupal\commerce_paytrail\Entity\PaymentMethod;
use Drupal\commerce_paytrail\Repository\FormManager;
use Drupal\commerce_paytrail\Repository\Product\Product;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * FormManager unit tests.
 *
 * @group commerce_paytrail
 * @coversDefaultClass \Drupal\commerce_paytrail\Repository\FormManager
 */
class FormManagerTest extends UnitTestCase {

  /**
   * The form manager.
   *
   * @var \Drupal\commerce_paytrail\Repository\FormManager
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->sut = new FormManager('12345', '12345');
  }

  /**
   * Tests the default values.
   *
   * @covers ::build
   * @covers ::__construct
   * @covers ::setParamsOut
   * @covers ::setParamsIn
   */
  public function testDefaults() {
    $manager = new FormManager('13466', '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ');
    $build = $manager->build();

    $expected = [
      'PARAMS_IN' => 'MERCHANT_ID,PARAMS_IN,PARAMS_OUT',
      'MERCHANT_ID' => '13466',
      'PARAMS_OUT' => 'ORDER_NUMBER,PAYMENT_ID,PAYMENT_METHOD,TIMESTAMP,STATUS',
    ];
    $this->assertEquals($expected, $build);
  }

  /**
   * Tests ::assertPhone().
   *
   * @covers \Drupal\commerce_paytrail\AssertTrait::assertPhone
   */
  public function testAssertPhone() {
    $data = [
      '040213121_3123' => TRUE,
      '033213:231' => TRUE,
      'dsadsad0404' => TRUE,
      '040123333' => FALSE,
      '040-212333' => FALSE,
      '044+3432312' => FALSE,
      '040 423332' => FALSE,
    ];
    foreach ($data as $number => $expectedException) {
      $exception = FALSE;
      try {
        $this->sut->assertPhone($number);
      }
      catch (\InvalidArgumentException $e) {
        $exception = TRUE;
      }
      $this->assertEquals($expectedException, $exception);
    }
  }

  /**
   * Tests ::assertPostalCode().
   *
   * @covers \Drupal\commerce_paytrail\AssertTrait::assertPostalCode
   */
  public function testAssertPostalCode() {
    $data = [
      'wwww:dsd' => TRUE,
      'dasda//dsa' => TRUE,
      'CR2 6XH' => FALSE,
      '123456' => FALSE,
      '12345AW' => FALSE,
      'W134555A' => FALSE,
      'w123Wa' => FALSE,
    ];
    foreach ($data as $code => $expectedException) {
      $exception = FALSE;
      try {
        $this->sut->assertPostalCode($code);
      }
      catch (\InvalidArgumentException $e) {
        $exception = TRUE;
      }
      $this->assertEquals($expectedException, $exception);
    }
  }

  /**
   * Tests ::assertValidUrl().
   *
   * @covers \Drupal\commerce_paytrail\AssertTrait::assertValidUrl
   */
  public function testAssertValidUrl() {
    $data = [
      'http://' => TRUE,
      'localhost' => TRUE,
      'http://localhost' => FALSE,
      'http://example.com' => FALSE,
    ];

    foreach ($data as $number => $expectedException) {
      $exception = FALSE;
      try {
        $this->sut->assertValidUrl($number);
      }
      catch (\InvalidArgumentException $e) {
        $exception = TRUE;
      }
      $this->assertEquals($expectedException, $exception);
    }
  }

  /**
   * Tests ::assertBetween().
   *
   * @covers \Drupal\commerce_paytrail\AssertTrait::assertBetween
   * @covers \Drupal\commerce_paytrail\AssertTrait::assertAmountBetween
   * @dataProvider assertBetweenData
   */
  public function testAssertBetween($data) {
    list('exception' => $exception, 'num' => $num, 'min' => $min, 'max' => $max) = $data;

    $exceptionThrown = FALSE;

    try {
      $this->sut->assertBetween($num, $min, $max);
    }
    catch (\InvalidArgumentException $e) {
      $exceptionThrown = TRUE;
    }

    $this->assertEquals($exception, $exceptionThrown);

    $exceptionThrown = FALSE;

    try {
      $this->sut->assertAmountBetween(new Price($num, 'EUR'), $min, $max);
    }
    catch (\InvalidArgumentException $e) {
      $exceptionThrown = TRUE;
    }

    $this->assertEquals($exception, $exceptionThrown);
  }

  /**
   * Provides assert between data.
   *
   * @return array
   *   The data.
   */
  public function assertBetweenData() {
    return [
      [
        [
          'exception' => TRUE,
          'num' => 0.1,
          'min' => 0.65,
          'max' => 65,
        ],
        [
          'exception' => TRUE,
          'num' => 66,
          'min' => 0.65,
          'max' => 65,
        ],
        [
          'exception' => FALSE,
          'num' => 0.65,
          'min' => 0.65,
          'max' => 65,
        ],
        [
          'exception' => FALSE,
          'num' => 65,
          'min' => 0.65,
          'max' => 65,
        ],
        [
          'exception' => FALSE,
          'num' => 1,
          'min' => 0.65,
          'max' => 65,
        ],
      ],
    ];
  }

  /**
   * Tests ::assertText()
   *
   * @covers \Drupal\commerce_paytrail\AssertTrait::assertStrictText
   */
  public function testAssertText() {
    $data = [
      'Test: test' => TRUE,
      'T€st' => TRUE,
      'Test test 1234' => FALSE,
      'Test *test*, test "TEst" dsa' => FALSE,
      "Test {test} [test] 'test' -_-" => FALSE,
    ];
    foreach ($data as $number => $expectedException) {
      $exception = FALSE;
      try {
        $this->sut->assertStrictText($number);
      }
      catch (\InvalidArgumentException $e) {
        $exception = TRUE;
      }
      $this->assertEquals($expectedException, $exception);
    }
  }

  /**
   * Tests ::assertNonStrictText()
   *
   * @covers \Drupal\commerce_paytrail\AssertTrait::assertText
   */
  public function testAssertNonStrictText() {
    $data = [
      'TEst (11 €)' => TRUE,
      'Test: test $_,.:&!?@#$£' => FALSE,
      'Test test 1234' => FALSE,
      'Test *test*, test "TEst" dsa' => FALSE,
      "Test {test} [test] 'test' -_-" => FALSE,
    ];
    foreach ($data as $number => $expectedException) {
      $exception = FALSE;
      try {
        $this->sut->assertText($number);
      }
      catch (\InvalidArgumentException $e) {
        $exception = TRUE;
      }
      $this->assertEquals($expectedException, $exception);
    }
  }

  /**
   * Tests build with all available values.
   *
   * @covers ::build
   * @covers ::setAmount
   * @covers ::setMerchantId
   * @covers ::setSuccessUrl
   * @covers ::setCancelUrl
   * @covers ::setNotifyUrl
   * @covers ::setOrderNumber
   * @covers ::setMerchantPanelUiMessage
   * @covers ::setPaymentMethodUiMessage
   * @covers ::setPayerSettlementMessage
   * @covers ::setMerchantSettlementMessage
   * @covers ::setLocale
   * @covers ::setCurrency
   * @covers ::setReferenceNumber
   * @covers ::setPaymentMethods
   * @covers ::setPayerPhone
   * @covers ::setPayerEmail
   * @covers ::setPayerFirstName
   * @covers ::setPayerLastName
   * @covers ::setPayerCompany
   * @covers ::setPayerAddress
   * @covers ::setPayerPostalCode
   * @covers ::setPayerCity
   * @covers ::setPayerCountry
   * @covers ::setIsVatIncluded
   * @covers ::setAlg
   * @covers ::removeValue
   * @covers ::setValue
   * @covers \Drupal\commerce_paytrail\Entity\PaymentMethod
   */
  public function testBuild() {
    $entityManager = $this->getMock(EntityManagerInterface::class);
    $container = new ContainerBuilder();
    $container->set('entity.manager', $entityManager);

    \Drupal::setContainer($container);

    $entities = [
      new PaymentMethod([
        'id' => '1',
        'label' => 'Label',
      ], 'paytrail_payment_method'),
      new PaymentMethod([
        'id' => '2',
        'label' => 'Label',
      ], 'paytrail_payment_method'),
    ];

    $manager = new FormManager('13466', '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ');
    $manager->setAmount(new Price('123', 'EUR'))
      ->setMerchantId('12345')
      ->setSuccessUrl('http://localhost/success')
      ->setCancelUrl('http://localhost/cancel')
      ->setNotifyUrl('http://localhost/notify')
      ->setOrderNumber('123')
      ->setMerchantPanelUiMessage('merchant_message')
      ->setPaymentMethodUiMessage('payment_ui_message')
      ->setPayerSettlementMessage('payment_settlement_message')
      ->setMerchantSettlementMessage('merchant_settlement_message')
      ->setLocale('fi_FI')
      ->setCurrency('EUR')
      ->setReferenceNumber('234')
      ->setPaymentMethods($entities)
      ->setPayerPhone('040 123456')
      ->setPayerEmail('test@localhost')
      ->setPayerFirstName('Firstname')
      ->setPayerLastName('Lastname')
      ->setPayerCompany('Company')
      ->setPayerAddress('Street 1')
      ->setPayerPostalCode('00100')
      ->setPayerCity('Helsinki')
      ->setPayerCountry('FI')
      ->setIsVatIncluded(TRUE)
      ->setAlg(1);

    $expected = [
      'PARAMS_IN' => 'MERCHANT_ID,PARAMS_IN,AMOUNT,URL_SUCCESS,URL_CANCEL,URL_NOTIFY,ORDER_NUMBER,MSG_UI_MERCHANT_PANEL,MSG_UI_PAYMENT_METHOD,MSG_SETTLEMENT_PAYER,MSG_SETTLEMENT_MERCHANT,LOCALE,CURRENCY,REFERENCE_NUMBER,PAYMENT_METHODS,PAYER_PERSON_PHONE,PAYER_PERSON_EMAIL,PAYER_PERSON_FIRSTNAME,PAYER_PERSON_LASTNAME,PAYER_COMPANY_NAME,PAYER_PERSON_ADDR_STREET,PAYER_PERSON_ADDR_POSTAL_CODE,PAYER_PERSON_ADDR_TOWN,PAYER_PERSON_ADDR_COUNTRY,VAT_IS_INCLUDED,ALG,PARAMS_OUT',
      'MERCHANT_ID' => '12345',
      'PARAMS_OUT' => 'ORDER_NUMBER,PAYMENT_ID,PAYMENT_METHOD,TIMESTAMP,STATUS',
      'AMOUNT' => '123',
      'URL_SUCCESS' => 'http://localhost/success',
      'URL_CANCEL' => 'http://localhost/cancel',
      'URL_NOTIFY' => 'http://localhost/notify',
      'ORDER_NUMBER' => '123',
      'MSG_UI_MERCHANT_PANEL' => 'merchant_message',
      'MSG_UI_PAYMENT_METHOD' => 'payment_ui_message',
      'MSG_SETTLEMENT_PAYER' => 'payment_settlement_message',
      'MSG_SETTLEMENT_MERCHANT' => 'merchant_settlement_message',
      'LOCALE' => 'fi_FI',
      'CURRENCY' => 'EUR',
      'REFERENCE_NUMBER' => '234',
      'PAYMENT_METHODS' => '1,2',
      'PAYER_PERSON_PHONE' => '040 123456',
      'PAYER_PERSON_EMAIL' => 'test@localhost',
      'PAYER_PERSON_FIRSTNAME' => 'Firstname',
      'PAYER_PERSON_LASTNAME' => 'Lastname',
      'PAYER_COMPANY_NAME' => 'Company',
      'PAYER_PERSON_ADDR_STREET' => 'Street 1',
      'PAYER_PERSON_ADDR_POSTAL_CODE' => '00100',
      'PAYER_PERSON_ADDR_TOWN' => 'Helsinki',
      'PAYER_PERSON_ADDR_COUNTRY' => 'FI',
      'VAT_IS_INCLUDED' => '1',
      'ALG' => '1',
    ];

    $this->assertEquals($expected, $manager->build());

    // Make sure setting vat is included removes the value from params in.
    $manager->setIsVatIncluded(FALSE);

    unset($expected['VAT_IS_INCLUDED']);
    $expected['PARAMS_IN'] = 'MERCHANT_ID,PARAMS_IN,AMOUNT,URL_SUCCESS,URL_CANCEL,URL_NOTIFY,ORDER_NUMBER,MSG_UI_MERCHANT_PANEL,MSG_UI_PAYMENT_METHOD,MSG_SETTLEMENT_PAYER,MSG_SETTLEMENT_MERCHANT,LOCALE,CURRENCY,REFERENCE_NUMBER,PAYMENT_METHODS,PAYER_PERSON_PHONE,PAYER_PERSON_EMAIL,PAYER_PERSON_FIRSTNAME,PAYER_PERSON_LASTNAME,PAYER_COMPANY_NAME,PAYER_PERSON_ADDR_STREET,PAYER_PERSON_ADDR_POSTAL_CODE,PAYER_PERSON_ADDR_TOWN,PAYER_PERSON_ADDR_COUNTRY,ALG,PARAMS_OUT';

    $this->assertEquals($expected, $manager->build());
  }

  /**
   * Tests setProduct() methods.
   *
   * @covers ::setProduct
   * @covers ::setProducts
   */
  public function testProducts() {
    $product = (new Product())
      ->setTitle('Title')
      ->setItemId('1')
      ->setQuantity(1)
      ->setPrice(new Price('11', 'EUR'));

    $product2 = (new Product())
      ->setTitle('Title 2')
      ->setItemId('2')
      ->setQuantity(1)
      ->setPrice(new Price('23', 'EUR'));

    $manager = new FormManager('13466', '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ');
    $manager->setAmount(new Price('123', 'EUR'))
      ->setProduct($product);

    $expected = [
      'PARAMS_IN' => 'MERCHANT_ID,PARAMS_IN,ITEM_TITLE[0],ITEM_QUANTITY[0],ITEM_UNIT_PRICE[0],ITEM_VAT_PERCENT[0],ITEM_DISCOUNT_PERCENT[0],ITEM_TYPE[0],ITEM_ID[0],PARAMS_OUT',
      'MERCHANT_ID' => '13466',
      'PARAMS_OUT' => 'ORDER_NUMBER,PAYMENT_ID,PAYMENT_METHOD,TIMESTAMP,STATUS',
      'ITEM_TITLE[0]' => 'Title',
      'ITEM_QUANTITY[0]' => '1',
      'ITEM_UNIT_PRICE[0]' => '11.00',
      'ITEM_VAT_PERCENT[0]' => '0.00',
      'ITEM_DISCOUNT_PERCENT[0]' => '0',
      'ITEM_TYPE[0]' => '1',
      'ITEM_ID[0]' => '1',
    ];
    $this->assertEquals($expected, $manager->build());

    // Make sure we can override products.
    $manager->setProducts([$product2]);

    $expected['ITEM_TITLE[0]'] = 'Title 2';
    $expected['ITEM_UNIT_PRICE[0]'] = '23.00';
    $expected['ITEM_ID[0]'] = '2';
    $this->assertEquals($expected, $manager->build());
  }

  /**
   * Tests validations.
   */
  public function testExceptions() {
    $manager = new FormManager('13466', '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ');

    $exception = FALSE;
    try {
      $manager->setLocale('en_GB');
    }
    catch (\InvalidArgumentException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    $exception = FALSE;
    try {
      $manager->setPaymentMethods([1]);
    }
    catch (\InvalidArgumentException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    $exception = FALSE;
    try {
      $manager->setPayerCountry('FIN');
    }
    catch (\InvalidArgumentException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);

    $exception = FALSE;
    try {
      $manager->setProducts([1]);
    }
    catch (\InvalidArgumentException $e) {
      $exception = TRUE;
    }
    $this->assertTrue($exception);
  }

  /**
   * Tests generateReturnChecksum() method.
   *
   * @covers ::generateAuthCode
   * @dataProvider generateReturnChecksumProvider
   */
  public function testGenerateReturnChecksum($values, $expected) {
    $manager = new FormManager('13466', '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ');
    $return = $manager->generateReturnChecksum($values);
    $this->assertEquals($return, $expected);
  }

  /**
   * Data provider for testGenerateReturnChecksum().
   */
  public function generateReturnChecksumProvider() {
    return [
      [
        [1, 2, 3, 4],
        '6A14387E77136D78A1859AE508E4642EAA82BD66A3F46485795D8D61211B4F14',
      ],
      [
        ['s' => '123', 'd' => '22'],
        '1DD0B2B1FD4F2816AFA00AA33E97ABBFD1C3BEE14438F932B240D0A59564C03F',
      ],
    ];
  }

  /**
   * Tests generateAuthCode() method.
   *
   * @covers ::generateAuthCode
   * @dataProvider generateAuthCodeProvider
   */
  public function testGenerateAuthCode($values, $expected) {
    $manager = new FormManager('13466', '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ');
    $return = $manager->generateAuthCode($values);
    $this->assertEquals($return, $expected);
  }

  /**
   * Data provider for testGenerateAuthCode().
   */
  public function generateAuthCodeProvider() {
    return [
      [
        [
          'test' => 1,
          'test2' => '233',
          'value' => 'jo0',
        ],
        'CBAF6742675ABB962EF56327862838CDBFDA92F9F71FC29E08721AFAC0526856',
      ],
      [
        [1, 2, 3, 4, 5],
        'D732E2333E6E3F0F355EC7FC54EA2DF72E5184D12AB696CED7CFAE99636343A8',
      ],
    ];
  }

}
