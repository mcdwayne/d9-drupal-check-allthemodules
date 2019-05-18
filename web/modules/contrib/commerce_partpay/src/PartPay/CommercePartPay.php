<?php

namespace Drupal\commerce_partpay\PartPay;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides Base Part Pay class.
 */
abstract class CommercePartPay extends OffsitePaymentGatewayBase implements CommercePartPayInterface {

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {

    /* @var \Drupal\commerce_partpay\PartPay\PartPay $partPay */
    $partPay = $this->partPay;

    $response = $partPay->getOrder($request->get('orderId'));
    if (!$response) {
      $message = $this->t(
        'Sorry @gateway failed with "@message". You may resume the checkout process on this page when you are ready.',
        [
          '@message' => ucwords(strtolower($response->getReasonPhrase())),
          '@gateway' => $this->getDisplayLabel(),
        ]
      );

      \Drupal::messenger()->addMessage($message, 'error');
      return;
    }
    parent::onCancel($order, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

    /* @var \Drupal\commerce_partpay\PartPay\PartPay $partPay */
    $partPay = $this->partPay;

    $response = $partPay->getOrder($request->get('orderId'));

    if ($partPay->isSuccessful($response) && $order->state->value !== 'completed') {
      $this->capturePayment($order, $response);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(OrderInterface $order, \stdClass $response) {

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    $requestTime = \Drupal::time()->getRequestTime();

    $data = [
      'state' => 'completed',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_id' => $response->orderId,
      'remote_state' => $response->orderStatus,
      'authorized' => $requestTime,
      'completed' => $requestTime,
    ];

    $payment = $payment_storage->create($data);

    $payment->save();
  }

  /**
   * Refunds the given payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to refund.
   * @param \Drupal\commerce_price\Price $amount
   *   The amount to refund. If NULL, defaults to the entire payment amount.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    /* @var \Drupal\commerce_partpay\PartPay\PartPay $partPay */
    $partPay = $this->partPay;

    $refundRequestId = $payment->uuid() . '--' . date('Y-m-d');
    $merchantReference = $payment->uuid() . '--' . $payment->getOrderId() . '--' . date('YmdH') . '-xsdf';
    $body = [
      'id' => $refundRequestId,
      'amount' => $amount->getNumber(),
      'merchantRefundReference' => $merchantReference,
    ];

    $response = $partPay->refundOrder($payment->getRemoteId(), $body);
    if (get_class($response) == Response::class) {
      // Error.
      $message = $this->t(
        'Refund failed with error "@message".',
        ['@message' => $response->getBody()->getContents()]
      );
      \Drupal::messenger()->addMessage($message, 'error');
      return;
    }

    $refund = Price::fromArray([
      'number' => number_format($response->amount, 2),
      'currency_code' => $amount->getCurrencyCode(),
    ]);
    $refund = $refund->add($payment->getRefundedAmount());
    $payment->setRefundedAmount($refund);

    $refundState = 'refunded';
    if ($refund->lessThan($payment->getAmount())) {
      $refundState = 'partially_refunded';
    }

    $payment->setState($refundState);
    $payment->save();
  }

}
