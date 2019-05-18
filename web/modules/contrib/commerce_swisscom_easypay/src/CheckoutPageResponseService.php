<?php

namespace Drupal\commerce_swisscom_easypay;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_swisscom_easypay\Plugin\Commerce\PaymentGateway\CheckoutPage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Gridonic\EasyPay\CheckoutPage\CheckoutPageResponse;
use Gridonic\EasyPay\REST\DirectPaymentResponse;
use Gridonic\EasyPay\REST\RESTApiException;
use Gridonic\EasyPay\REST\RESTApiService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CheckoutPageResponseService.
 *
 * @package Drupal\commerce_swisscom_easypay
 */
class CheckoutPageResponseService {

  /**
   * The checkout page payment gateway plugin.
   *
   * @var \Drupal\commerce_swisscom_easypay\Plugin\Commerce\PaymentGateway\CheckoutPage
   */
  private $checkoutPage;

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The logger channel for the module.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * Easypay REST API service.
   *
   * @var \Gridonic\EasyPay\REST\RESTApiService
   */
  private $restApiService;

  /**
   * CheckoutPageResponse constructor.
   *
   * @param \Drupal\commerce_swisscom_easypay\Plugin\Commerce\PaymentGateway\CheckoutPage $checkoutPage
   *   The checkout page payment gateway plugin.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal's entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Drupal's logger channel for this module.
   * @param \Gridonic\EasyPay\REST\RESTApiService $restApiService
   *   Easypay REST API service.
   */
  public function __construct(CheckoutPage $checkoutPage, EntityTypeManagerInterface $entityTypeManager, LoggerChannelInterface $logger, RESTApiService $restApiService) {
    $this->checkoutPage = $checkoutPage;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
    $this->restApiService = $restApiService;
  }

  /**
   * Handle the "return" request (success or error).
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   A commerce order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Symfony request object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $checkoutPageResponse = new CheckoutPageResponse($request->query->all());

    if ($checkoutPageResponse->isSuccess()) {
      $this->commitPayment($order, $checkoutPageResponse->getPaymentId());
    }
    else {
      $message = sprintf('Checkout page error: %s', $checkoutPageResponse->getErrorCode());
      $this->logger->error($message);
      throw new InvalidResponseException($message);
    }
  }

  /**
   * Handle the "cancel" request.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   A commerce order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Symfony request object.
   */
  public function onCancel(OrderInterface $order, Request $request) {}

  /**
   * Commit the payment via Easypay REST API.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   A commerce order.
   * @param string $paymentId
   *   The Easypay payment-ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function commitPayment(OrderInterface $order, $paymentId) {
    // Store the payment ID in the order object.
    $order->setData('commerce_swisscom_easypay', ['payment_id' => $paymentId]);
    $order->save();

    try {
      $directPaymentResponse = $this->restApiService->directPayment($paymentId);

      if ($directPaymentResponse->isSuccess()) {
        // Obtain payment data, as the PUT request above does not include them.
        $directPaymentData = $this->restApiService->getDirectPayment($paymentId);
        $remoteStatus = $directPaymentData->getStatus() ?: '';
        $this->createCommercePayment($order, $paymentId, 'completed', $remoteStatus);
      }
      else {
        $this->handlePaymentCommitError($directPaymentResponse);
      }
    }
    catch (RESTApiException $e) {
      $this->logger->error('Easypay REST API is not reachable');
      throw new InvalidResponseException('Easypay REST API is not reachable', 0, $e);
    }
  }

  /**
   * Handle payment commit errors.
   *
   * @param \Gridonic\EasyPay\REST\DirectPaymentResponse $directPaymentResponse
   *   The payment response from the Easypay REST API.
   */
  protected function handlePaymentCommitError(DirectPaymentResponse $directPaymentResponse) {
    $errors = $directPaymentResponse->getErrorMessages();
    /** @var \Gridonic\EasyPay\REST\ErrorMessage $error */
    $error = array_pop($errors);

    $errorInfo = json_encode([
      'message' => $error->getMessage(),
      'code' => $error->getCode(),
      'field' => $error->getField(),
      'request_id' => $error->getRequestId(),
    ]);

    $message = sprintf('Error while committing payment: %s', $errorInfo);
    $this->logger->error($message);
    throw new InvalidResponseException($message);
  }

  /**
   * Create the commerce payment.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   A commerce order.
   * @param string $remoteId
   *   The remote-ID.
   * @param string $state
   *   The state payment state, e.g. 'completed'.
   * @param string $remoteState
   *   The remote state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createCommercePayment(OrderInterface $order, $remoteId, $state, $remoteState = '') {
    $paymentStorage = $this->entityTypeManager->getStorage('commerce_payment');

    $payment = $paymentStorage->create(
      [
        'state' => $state,
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $this->checkoutPage->getEntityId(),
        'order_id' => $order->id(),
        'remote_id' => $remoteId,
        'remote_state' => $remoteState,
      ]
    );

    $payment->save();
  }

}
