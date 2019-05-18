<?php

namespace Drupal\Tests\commerce_swisscom_easypay\Unit;

// @codingStandardsIgnoreFile

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_payment\PaymentStorageInterface;
use Drupal\commerce_swisscom_easypay\CheckoutPageResponseService;
use Drupal\commerce_swisscom_easypay\Plugin\Commerce\PaymentGateway\CheckoutPage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use Gridonic\EasyPay\REST\DirectPaymentResponse;
use Gridonic\EasyPay\REST\ErrorMessage;
use Gridonic\EasyPay\REST\RESTApiService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for the CheckoutPageResponseService class.
 *
 * @group commerce_swisscom_easypay
 */
class CheckoutPageResponseServiceTest extends UnitTestCase {

  public function testOnReturn_RedirectSuccessful_CommercePaymentGetsCreated() {
    $paymentId = 'paymentId-123';

    $checkoutPageResponseService = $this->getCheckoutPageResponseService(function ($checkoutPageMock, $entityTypeManagerMock, $loggerMock, $restApiServiceMock) use ($paymentId) {
      $directPaymentResponse = new DirectPaymentResponse();
      $directPaymentResponse->setIsSuccess(TRUE);

      $restApiServiceMock
        ->method('directPayment')
        ->with($paymentId)
        ->willReturn($directPaymentResponse);

      $restApiServiceMock
        ->method('getDirectPayment')
        ->with($paymentId)
        ->willReturn(new DirectPaymentResponse());

      $paymentStorageMock = $this->createMock(PaymentStorageInterface::class);
      $paymentStorageMock
        ->expects($this->once())
        ->method('create')
        ->willReturn($this->createMock(PaymentInterface::class));

      $entityTypeManagerMock
        ->expects($this->once())
        ->method('getStorage')
        ->willReturn($paymentStorageMock);
    });

    // Simulates a successful redirect response from Easypay
    $dummyRequest = new Request(['purchase' => 'success', 'paymentId' => $paymentId]);

    $orderMock = $this->createMock(OrderInterface::class);
    $orderMock
      ->expects($this->once())
      ->method('setData')
      ->with('commerce_swisscom_easypay', ['payment_id' => $paymentId]);

    $checkoutPageResponseService->onReturn($orderMock, $dummyRequest);
  }

  public function testOnReturn_RedirectNotSuccessful_ThrowsException() {
    $checkoutPageResponseService = $this->getCheckoutPageResponseService(function ($checkoutPageMock, $entityTypeManagerMock, $loggerMock, $restApiServiceMock) {
      $restApiServiceMock
        ->expects($this->never())
        ->method('directPayment');

      $entityTypeManagerMock
        ->expects($this->never())
        ->method('getStorage');
    });

    // Simulates an error redirect response from Easypay
    $dummyRequest = new Request(['purchase' => 'error']);

    $orderMock = $this->createMock(OrderInterface::class);
    $orderMock
      ->expects($this->never())
      ->method('setData');

    $this->setExpectedException(InvalidResponseException::class);

    $checkoutPageResponseService->onReturn($orderMock, $dummyRequest);
  }

  public function testOnReturn_RedirectSuccessfulButErrorWhileCommittingPayment_ThrowsException() {
    $checkoutPageResponseService = $this->getCheckoutPageResponseService(function ($checkoutPageMock, $entityTypeManagerMock, $loggerMock, $restApiServiceMock) {
      $directPaymentResponse = new DirectPaymentResponse();
      $directPaymentResponse
        ->setIsSuccess(FALSE)
        ->setErrorMessages([new ErrorMessage()]);

      $restApiServiceMock
        ->expects($this->once())
        ->method('directPayment')
        ->willReturn($directPaymentResponse);

      $entityTypeManagerMock
        ->expects($this->never())
        ->method('getStorage');
    });

    $dummyRequest = new Request(['purchase' => 'success', 'paymentId' => 'paymentId-123']);
    $orderMock = $this->createMock(OrderInterface::class);

    $this->setExpectedException(InvalidResponseException::class);

    $checkoutPageResponseService->onReturn($orderMock, $dummyRequest);
  }

  /**
   * @param \closure $dependencyManipulator
   *
   * @return \Drupal\commerce_swisscom_easypay\CheckoutPageResponseService
   */
  protected function getCheckoutPageResponseService(\closure $dependencyManipulator = NULL) {
    $checkoutPageMock = $this->createMock(CheckoutPage::class);
    $entityTypeManagerMock = $this->createMock(EntityTypeManagerInterface::class);
    $loggerMock = $this->createMock(LoggerChannelInterface::class);
    $restApiServiceMock = $this->createMock(RESTApiService::class);

    if (is_callable($dependencyManipulator)) {
      $dependencyManipulator($checkoutPageMock, $entityTypeManagerMock, $loggerMock, $restApiServiceMock);
    }

    return new CheckoutPageResponseService($checkoutPageMock, $entityTypeManagerMock, $loggerMock, $restApiServiceMock);
  }
}
