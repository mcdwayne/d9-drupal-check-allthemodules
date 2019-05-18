<?php

namespace Drupal\Tests\commerce_worldline\Unit;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_worldline\ValidateExternalPayment;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ValidateExternalPaymentTest.
 *
 * @group commerce_worldline
 */
class ValidateExternalPaymentTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $logger_channel = $this->getMock(LoggerChannelInterface::class);
    $logger_factory = $this->getMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($logger_channel);

    $container = new ContainerBuilder();
    $container->set('logger.factory', $logger_factory);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Tests invalid validation.
   *
   * No sha seal found.
   */
  public function testInvalidSha() {
    $payment = $this->getMock(PaymentInterface::class);
    $storage = $this->getMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn($payment);
    $em = $this->getMock(EntityTypeManagerInterface::class);
    $em->expects($this->exactly(1))
      ->method('getStorage')
      ->willReturn($storage);

    $sut = new ValidateExternalPayment($em, ['sips_passphrase' => 'foo']);

    $request = [
      'Data' => 'captureDay=0|captureMode=AUTHOR_CAPTURE|currencyCode=978|merchantId=123123|orderChannel=INTERNET|responseCode=00|transactionDateTime=2017-02-04T12:41:49+01:00|transactionReference=29|keyVersion=2|acquirerResponseCode=00|amount=1200|authorisationId=116058|guaranteeIndicator=Y|panExpiryDate=202201|paymentMeanBrand=MASTERCARD|paymentMeanType=CARD|customerIpAddress=127.0.9.1|maskedPan=5017##########00|holderAuthentRelegation=N|holderAuthentStatus=3D_SUCCESS|transactionOrigin=INTERNET|paymentPattern=ONE_SHOT',
      'Seal' => 'foo',
      'InterfaceVersion' => 'HP_2.0',
      'Encode' => '',
    ];
    $request = new Request([], $request);
    $order = $this->getMock(OrderInterface::class);
    $order->payment_method = new \stdClass();
    $order->payment_method->entity = new \stdClass();

    $this->setExpectedException(PaymentGatewayException::class, 'An error occurred while processing your request.');
    $sut->validateRequest($request, $order);
  }

  /**
   * Tests invalid validation.
   *
   * No payment found.
   */
  public function testNoPaymentValidation() {
    $payment = NULL;
    $storage = $this->getMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn($payment);
    $em = $this->getMock(EntityTypeManagerInterface::class);
    $em->expects($this->exactly(1))
      ->method('getStorage')
      ->willReturn($storage);

    $sut = new ValidateExternalPayment($em, ['sips_passphrase' => 'foo']);

    $responsecode = '00';
    $request = [
      'Data' => "captureDay=0|captureMode=AUTHOR_CAPTURE|currencyCode=978|merchantId=123123|orderChannel=INTERNET|responseCode={$responsecode}|transactionDateTime=2017-02-04T12:41:49+01:00|transactionReference=29|keyVersion=2|acquirerResponseCode=00|amount=1200|authorisationId=116058|guaranteeIndicator=Y|panExpiryDate=202201|paymentMeanBrand=MASTERCARD|paymentMeanType=CARD|customerIpAddress=127.0.9.1|maskedPan=5017##########00|holderAuthentRelegation=N|holderAuthentStatus=3D_SUCCESS|transactionOrigin=INTERNET|paymentPattern=ONE_SHOT",
      'Seal' => '22643ef50d503cc29a89f338f08d38fb63cd9fb3699e6f4139c12f1ea90ac0e5',
      'InterfaceVersion' => 'HP_2.0',
      'Encode' => '',
    ];

    $request = new Request([], $request);
    $order = $this->getMock(OrderInterface::class);
    $order->payment_method = new \stdClass();
    $order->payment_method->entity = new \stdClass();

    $this->setExpectedException(PaymentGatewayException::class, 'An error occurred while processing your request.');
    $sut->validateRequest($request, $order);
  }

  /**
   * Tests invalid validation.
   *
   * Failed at the external gateway
   */
  public function testFailedAtGateway() {
    $payment = $this->getMock(PaymentInterface::class);
    $payment->expects($this->exactly(1))
      ->method('setRemoteState')
      ->with('failed');
    $payment->expects($this->exactly(1))
      ->method('setState')
      ->with('void');
    $storage = $this->getMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn($payment);
    $em = $this->getMock(EntityTypeManagerInterface::class);
    $em->expects($this->exactly(1))
      ->method('getStorage')
      ->willReturn($storage);

    $sut = new ValidateExternalPayment($em, ['sips_passphrase' => 'foo']);

    $responsecode = '17';
    $request = [
      'Data' => "captureDay=0|captureMode=AUTHOR_CAPTURE|currencyCode=978|merchantId=123123|orderChannel=INTERNET|responseCode={$responsecode}|transactionDateTime=2017-02-04T12:41:49+01:00|transactionReference=29|keyVersion=2|acquirerResponseCode=00|amount=1200|authorisationId=116058|guaranteeIndicator=Y|panExpiryDate=202201|paymentMeanBrand=MASTERCARD|paymentMeanType=CARD|customerIpAddress=127.0.9.1|maskedPan=5017##########00|holderAuthentRelegation=N|holderAuthentStatus=3D_SUCCESS|transactionOrigin=INTERNET|paymentPattern=ONE_SHOT",
      'Seal' => '5060c149a97ecbbd6e14a997a1b5c93c0c3786120831b962d4b03dbc71f68334',
      'InterfaceVersion' => 'HP_2.0',
      'Encode' => '',
    ];

    $request = new Request([], $request);
    $order = $this->getMock(OrderInterface::class);

    $this->setExpectedException(PaymentGatewayException::class, 'An error occurred in the SIPS platform: [Buyer cancellation] 17');
    $sut->validateRequest($request, $order);
  }

  /**
   * Tests valid validation.
   */
  public function testValidation() {
    $responsecode = '00';
    $request = [
      'Data' => "captureDay=0|captureMode=AUTHOR_CAPTURE|currencyCode=978|merchantId=123123|orderChannel=INTERNET|responseCode={$responsecode}|transactionDateTime=2017-02-04T12:41:49+01:00|transactionReference=29|keyVersion=2|acquirerResponseCode=00|amount=1200|authorisationId=116058|guaranteeIndicator=Y|panExpiryDate=202201|paymentMeanBrand=MASTERCARD|paymentMeanType=CARD|customerIpAddress=127.0.9.1|maskedPan=5017##########00|holderAuthentRelegation=N|holderAuthentStatus=3D_SUCCESS|transactionOrigin=INTERNET|paymentPattern=ONE_SHOT",
      'Seal' => '22643ef50d503cc29a89f338f08d38fb63cd9fb3699e6f4139c12f1ea90ac0e5',
      'InterfaceVersion' => 'HP_2.0',
      'Encode' => '',
    ];

    $payment = $this->getMock(PaymentInterface::class);
    $payment->expects($this->exactly(1))
      ->method('set')
      ->withConsecutive(['sips_response_code', $responsecode]);

    $storage = $this->getMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn($payment);

    $em = $this->getMock(EntityTypeManagerInterface::class);
    $em->expects($this->exactly(1))
      ->method('getStorage')
      ->willReturn($storage);

    $request = new Request([], $request);
    $order = $this->getMock(OrderInterface::class);
    $order->payment = new \stdClass();
    $order->payment->entity = $payment;

    $sut = new ValidateExternalPayment($em, ['sips_passphrase' => 'foo']);
    $sut->validateRequest($request, $order);
  }

}

namespace Drupal\commerce_worldline;

if (!function_exists('drupal_set_message')) {

  /**
   * Drupal set message.
   */
  function drupal_set_message() {

  }

}
