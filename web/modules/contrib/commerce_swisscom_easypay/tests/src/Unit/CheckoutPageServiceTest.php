<?php

namespace Drupal\Tests\commerce_swisscom_easypay\Unit;

// @codingStandardsIgnoreFile

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_price\Price;
use Drupal\commerce_swisscom_easypay\CheckoutPageService;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Tests for the CheckoutPageService class.
 *
 * @group commerce_swisscom_easypay
 */
class CheckoutPageServiceTest extends UnitTestCase {

  public function testGetCheckoutPageUrl_PluginConfigHasTestOrLiveMode_ReturnsCorrectBaseUrl() {
    $checkoutPageService = $this->getCheckoutPageService(function(&$config, $eventDispatcherMock, $languageManagerMock) {
      $config['mode'] = 'test';
    });

    $checkoutPageUrl = $checkoutPageService->getCheckoutPageUrl($this->getOrderMock(), $this->getOffsitePaymentFormData());
    $this->assertEquals(0, strpos($checkoutPageUrl, 'https://easypay-staging.swisscom.ch'));

    $checkoutPageService = $this->getCheckoutPageService(function(&$config, $eventDispatcherMock, $languageManagerMock) {
      $config['mode'] = 'live';
    });

    $checkoutPageUrl = $checkoutPageService->getCheckoutPageUrl($this->getOrderMock(), $this->getOffsitePaymentFormData());
    $this->assertEquals(0, strpos($checkoutPageUrl, 'https://easypay.swisscom.ch'));
  }

  public function testGetCheckoutPageUrl_MappingOfOrderAndConfigToCheckoutPageItem_DataGetsMappedCorrectly() {
    $checkoutPageService = $this->getCheckoutPageService();
    $checkoutPageUrl = $checkoutPageService->getCheckoutPageUrl($this->getOrderMock(), $this->getOffsitePaymentFormData());

    preg_match('#checkoutRequestItem=(.*)&signature#', $checkoutPageUrl, $matches);
    $data = json_decode(base64_decode(urldecode($matches[1])));

    $this->assertEquals('merchant-123', $data->merchantId);
    $this->assertEquals('Checkout page title', $data->title);
    $this->assertEquals('2 x Dummy Product', $data->description);
    $this->assertEquals('19.90', $data->amount);
    $this->assertEquals('Payment information', $data->paymentInfo);
    $this->assertEquals('/url/to/image', $data->imageUrl);
    $this->assertEquals('/url/return', $data->successUrl);
    $this->assertEquals('/url/cancel', $data->cancelUrl);
    $this->assertEquals('/url/return', $data->errorUrl);
    $this->assertEquals(100, $data->cpUserId);
    $this->assertEquals(123, $data->cpServiceId);
    $this->assertEquals('en', $data->userLanguage);
  }

  public function testGetCheckoutPageUrl_MissingMandatoryRequestParameters_ThrowsException() {
    $checkoutPageService = $this->getCheckoutPageService(function(&$config, $eventDispatcherMock, $languageManagerMock) {
      // Empty the required payment information parameter
      $config['payment_info'] = '';
    });

    $this->setExpectedException(PaymentGatewayException::class);
    $checkoutPageService->getCheckoutPageUrl($this->getOrderMock(), $this->getOffsitePaymentFormData());
  }

  protected function getOffsitePaymentFormData() {
    return [
      '#return_url' => '/url/return',
      '#error_url' => '/url/error',
      '#cancel_url' => '/url/cancel',
    ];
  }

  protected function getOrderMock() {
    $orderMock = $this->createMock(OrderInterface::class);

    $orderItem = $this->createMock(OrderItemInterface::class);
    $orderItem
      ->method('getQuantity')
      ->willReturn(2);

    $orderItem
      ->method('getTitle')
      ->willReturn('Dummy Product');

    $orderMock
      ->method('getItems')
      ->willReturn([$orderItem]);

    $orderMock
      ->method('getTotalPrice')
      ->willReturn(new Price('19.90', 'CHF'));

    $orderMock
      ->method('getCustomerId')
      ->willReturn(100);

    $orderMock
      ->method('id')
      ->willReturn(123);

    return $orderMock;
  }

  /**
   * @param \closure $dependencyManipulator
   *
   * @return \Drupal\commerce_swisscom_easypay\CheckoutPageService
   */
  protected function getCheckoutPageService(\closure $dependencyManipulator = NULL) {
    $config = $this->getPluginConfig();
    $eventDispatcherMock = $this->createMock(EventDispatcher::class);
    $languageManagerMock = $this->createMock(LanguageManagerInterface::class);

    $languageManagerMock
      ->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));

    if (is_callable($dependencyManipulator)) {
      $dependencyManipulator($config, $eventDispatcherMock, $languageManagerMock);
    }

    return new CheckoutPageService($config, $eventDispatcherMock, $languageManagerMock);
  }

  /**
   * @return array
   */
  protected function getPluginConfig() {
    return [
      'mode' => 'test',
      'merchant_id' => 'merchant-123',
      'secret' => 's3cr3t',
      'checkout_page_title' => 'Checkout page title',
      'checkout_page_description' => '',
      'checkout_page_image_url' => '/url/to/image',
      'payment_info' => 'Payment information',
    ];
  }

}
