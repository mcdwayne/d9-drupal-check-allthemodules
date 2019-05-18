<?php

namespace Drupal\commerce_webpay_by\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_webpay_by\Common\Helper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WebpayByRedirectController.
 */
class WebpayByRedirectController extends ControllerBase {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   *   Logger
   */
  private $logger;

  /**
   * The entity storage with a commerce payment.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   *   Entity Storage
   */
  private $entityStorage;

  /**
   * WebpayByRedirectController constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Current logger chanel.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entityStorage
   *   Entity storage.
   */
  public function __construct(LoggerInterface $logger, EntityStorageInterface $entityStorage) {
    $this->logger = $logger;
    $this->entityStorage = $entityStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $logger = $container->get('logger.factory')->get('commerce_webpay_by');
    $entityStorage = $container->get('entity_type.manager')
      ->get('commerce_payment');

    return new static($logger, $entityStorage);
  }

  /**
   * Build signature to compare with came in request.
   *
   * @param array $data
   *   Request data.
   *   $data = [
   *     'batch_timestamp' => (string)
   *     'currency_id'     => (string)
   *     'amount'          => (string)
   *     'payment_method'  => (string)
   *     'order_id'        => (string)
   *     'site_order_id'   => (string)
   *     'transaction_id'  => (string)
   *     'payment_type'    => (string)
   *     'rrn'             => (string)
   *     'secret_key'      => (string)
   *   ].
   *
   * @see https://webpay.by/wp-content/uploads/2016/08/WebPay-Developer-Guide-2.1.2_EN.pdf#page=20
   *   The "Upon Payment Notification" section.
   *
   * @return string
   *   The signature.
   */
  public function buildSignature(array $data) {
    $signature = '';
    try {
      $signature .= Helper::fetchArrayValueByKey('batch_timestamp', $data);
      $signature .= Helper::fetchArrayValueByKey('currency_id', $data);
      $signature .= Helper::fetchArrayValueByKey('amount', $data);
      $signature .= Helper::fetchArrayValueByKey('payment_method', $data);
      $signature .= Helper::fetchArrayValueByKey('order_id', $data);
      $signature .= Helper::fetchArrayValueByKey('site_order_id', $data);
      $signature .= Helper::fetchArrayValueByKey('transaction_id', $data);
      $signature .= Helper::fetchArrayValueByKey('payment_type', $data);
      $signature .= Helper::fetchArrayValueByKey('rrn', $data);
      $signature .= Helper::fetchArrayValueByKey('secret_key', $data);
    }
    catch (\OutOfBoundsException $exception) {
      $this->logger->error($this->t('Signature of reference was wrong! @exception'));
      throw $exception;
    }

    return md5($signature);
  }

  /**
   * Notify page callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function notify(Request $request) {
    $data = $request->request->all();

    if ($data['site_order_id']) {
      $order = $this->getOrder($data['site_order_id']);
      /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
      $payment_gateway = $order->get('payment_gateway')->first()->entity;
      $data['secret_key'] = $payment_gateway->get('configuration')['secret_key'] ?? '';
      $signature = $this->buildSignature($data);

      if ($signature === $data['wsb_signature']) {
        try {
          $this->completePayment($order, $data);
          return new Response();
        }
        catch (\Exception $e) {
          $message = $this->t('Error during creating new payment: @exception', [
            '@exception' => $e->getMessage(),
          ]);
          $this->logger->emergency($message, [
            'link' => $order->toLink('Order')->toString(),
          ]);
        }
      }
      else {
        $message = $this->t('Signature of payment was wrong!');
        $this->logger->error($message, [
          'link' => $order->toLink('Order')->toString(),
        ]);
      }
    }
    else {
      $message = $this->t('No order id defined in the API request!');
      $this->logger->emergency($message);
    }

    return new Response('', 403);
  }

  /**
   * Get order by ID.
   *
   * @param int $order_id
   *   Needle order ID.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder(int $order_id) {
    return Order::load($order_id);
  }

  /**
   * Complete new payment.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   An order.
   * @param array $data
   *   Data from the request.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function completePayment(OrderInterface $order, array $data) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
    $payment_gateway = $order->get('payment_gateway')->first()->entity;
    $payment = $this->entityStorage->create([
      'state' => 'completed',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $payment_gateway->id(),
      'order_id' => $order->id(),
      'remote_id' => $data['order_id'],
      'remote_state' => $data['payment_method'],
    ]);
    $payment->save();

    $message = $this->t('New payment for order #@order', ['@order' => $order->id()]);
    $this->logger->info($message, [
      'link' => $order->toLink('Order')->toString(),
    ]);
  }

}
