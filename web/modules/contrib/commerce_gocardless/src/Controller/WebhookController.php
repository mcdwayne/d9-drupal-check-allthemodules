<?php

namespace Drupal\commerce_gocardless\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentStorageInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles webhook requests from GoCardless.
 */
class WebhookController extends ControllerBase {

  /**
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\commerce_payment\PaymentStorageInterface $payment_storage
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   */
  public function __construct(PaymentStorageInterface $payment_storage, LoggerChannelInterface $logger) {
    $this->paymentStorage = $payment_storage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('commerce_payment'),
      $container->get('logger.factory')->get('commerce_gocardless')
    );
  }

  /**
   * Provides the entry point for webhook requests from GoCardless.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function webhook(PaymentGatewayInterface $payment_gateway, Request $request) {
    $raw_payload = $request->getContent();

    /** @var \Drupal\commerce_gocardless\Plugin\Commerce\PaymentGateway\GoCardlessPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $payment_gateway_id = $payment_gateway->id();
    $webhook_secret = $payment_gateway_plugin->getWebhookSecret();

    $provided_signature = $request->headers->get('Webhook-Signature');
    $calculated_signature = hash_hmac("sha256", $raw_payload, $webhook_secret);
    if ($provided_signature !== $calculated_signature) {
      return new Response("Provided signature does not match calculated signature for payment gateway {$payment_gateway_id}.", 498);
    }

    $object = Json::decode($raw_payload);
    foreach ($object['events'] as $event) {
      switch ($event['resource_type']) {
        case 'mandates':
          $this->handleMandateEvent($event);
          break;

        case 'payments':
          $this->handlePaymentEvent($event);
          break;

        case 'subscriptions':
          $this->handleSubscriptionEvent($event);
          break;

        default:
          $this->handleUnknownEvent($event);
          break;
      }
    }

    return new Response("Drupal received the data ok with payment gateway {$payment_gateway_id}.", 200);
  }

  /**
   * Handle a single mandate event.
   *
   * @param array $event
   *   Event details, see https://developer.gocardless.com/api-reference/#core-endpoints-events.
   */
  private function handleMandateEvent($event) {
    $logger = \Drupal::logger('commerce_gocardless');

    $mandate_id = $event['links']['mandate'];

    switch ($event['action']) {
      case 'created':
        $logger->info('Mandate created: {id}', ['id' => $mandate_id]);
        break;

      case 'submitted':
        $logger->info('Mandate submitted: {id}', ['id' => $mandate_id]);
        break;

      case 'active':
        $logger->info('Mandate active: {id}', ['id' => $mandate_id]);
        break;

      default:
        $logger->info('Message received about mandate {id}. Action is {action}.', [
          'id' => $mandate_id,
          'action' => $event['action'],
        ]);
    }
  }

  /**
   * Handle a single payment event.
   *
   * @param array $event
   *   Event details, see https://developer.gocardless.com/api-reference/#core-endpoints-events.
   */
  private function handlePaymentEvent($event) {
    $gc_payment_id = $event['links']['payment'];
    $action = $event['action'];

    $payment = $this->paymentStorage->loadByRemoteId($gc_payment_id);

    // If we don't know anything about the payment ignore this event but
    // leave a warning message in the log.
    if (!$payment) {
      $this->logger->warning('Unrecognised payment @gc_payment_id. Action: @action. Cause: @cause. Description: @description', [
        '@gc_payment_id' => $gc_payment_id,
        '@action' => $action,
        '@cause' => $event['details']['cause'],
        '@description' => $event['details']['description'],
      ]);
      return;
    }

    switch ($event['action']) {
      case 'created':
      case 'submitted':
      case 'paid_out':
        // Not interested in these.
        break;

      case 'customer_approval_granted':
      case 'customer_approval_denied':
        // Assuming that these events are followed by a 'confirmed' or 'failed'
        // event, so we don't need to handle them.
        break;

      case 'chargeback_cancelled':
      case 'late_failure_settled':
      case 'chargeback_settled':
      case 'resubmission_requested':
        // Probably not interested in these, but more research might be needed.
        break;

      case 'confirmed':
        $this->handlePaymentConfirmedEvent($payment, $event);
        break;

      case 'charged_back':
        $this->handlePaymentChargedBackEvent($payment, $event);
        break;

      case 'failed':
        $this->handlePaymentFailedEvent($payment, $event);
        break;

      case 'cancelled':
        $this->handlePaymentCancelledEvent($payment, $event);
        break;

      default:
        // Ignore all other actions but put a warning message in the log.
        $this->logger->warning('Order #@order_id: unrecognised message received about payment @gc_payment_id. Action: @action. Cause: @cause. Description: @description', [
          '@order_id' => $payment->getOrderId(),
          '@gc_payment_id' => $gc_payment_id,
          '@action' => $action,
          '@cause' => $event['details']['cause'],
          '@description' => $event['details']['description'],
        ]);
        return;
    }
  }

  /**
   * Handle a payment confirmation event.
   *
   * Changes the payment state to completed, and validates the order if
   * payment has been made in full.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   * @param array $event
   *   Event details, see https://developer.gocardless.com/api-reference/#core-endpoints-events.
   */
  private function handlePaymentConfirmedEvent(PaymentInterface $payment, $event) {
    // Transitions aren't used by payment API (see commerce_payment.workflows.yml)
    $payment->setState('completed');
    $payment->save();
    $this->logger->info('Order #@order_id: payment @gc_payment_id was confirmed.', [
      '@gc_payment_id' => $payment->getRemoteId(),
      '@order_id' => $payment->getOrderId(),
    ]);

    // Also update the order to reflect that we have received payment.
    // We need to do this programmatically here for now, see https://www.drupal.org/node/2856586
    $order = $payment->getOrder();
    if ($order->getTotalPrice() && $payment->getAmount() && $payment->getAmount()->greaterThanOrEqual($order->getTotalPrice())) {
      $this->validateOrder($order);
    }
  }

  /**
   * Validate the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   */
  private function validateOrder(OrderInterface $order) {
    $transition = $order->getState()->getWorkflow()->getTransition('validate');
    if (!$transition) {
      // TODO: this check can be removed once https://www.drupal.org/project/commerce/issues/2930512
      // gets in.
      $this->logger->warning('Unable to update order @order_number because the workflow does not support the "validate" transition.', [
        '@order_number' => $order->getOrderNumber(),
      ]);
      return;
    }
    $order->getState()->applyTransition($transition);
    $order->save();
  }

  /**
   * Handle a payment charged back event.
   *
   * Changes the payment state to refunded.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity.
   * @param array $event
   *   Event details, see https://developer.gocardless.com/api-reference/#core-endpoints-events.
   */
  private function handlePaymentChargedBackEvent(PaymentInterface $payment, $event) {
    // Transitions aren't used by payment API (see commerce_payment.workflows.yml)
    $payment->setState('refunded');
    $payment->save();
    $this->logger->info('Order #@order_id: payment @gc_payment_id charged back.', [
      '@gc_payment_id' => $payment->getRemoteId(),
      '@order_id' => $payment->getOrderId(),
    ]);
  }

  /**
   * Handle a payment failed event.
   *
   * Changes the payment state to voided.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity.
   * @param array $event
   *   Event details, see https://developer.gocardless.com/api-reference/#core-endpoints-events.
   */
  private function handlePaymentFailedEvent(PaymentInterface $payment, $event) {
    // Transitions aren't used by payment API (see commerce_payment.workflows.yml)
    // TODO: 'authorization_voided' isn't quite the right state, as it'll be
    // the capture that has failed rather than authorization. Add this state
    // to Commerce.
    $payment->setState('authorization_voided');
    $payment->save();
    $this->logger->info('Order #@order_id: payment @gc_payment_id failed.', [
      '@gc_payment_id' => $payment->getRemoteId(),
      '@order_id' => $payment->getOrderId(),
    ]);
  }

  /**
   * Handle a payment cancelled event.
   *
   * Changes the payment state to authorization_voided, for lack of anything
   * more suitable.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity.
   * @param array $event
   *   Event details, see https://developer.gocardless.com/api-reference/#core-endpoints-events.
   */
  private function handlePaymentCancelledEvent(PaymentInterface $payment, $event) {
    // Transitions aren't used by payment API (see commerce_payment.workflows.yml)
    $payment->setState('authorization_voided');
    $payment->save();
    $this->logger->info('Order #@order_id: payment @gc_payment_id cancelled.', [
      '@gc_payment_id' => $payment->getRemoteId(),
      '@order_id' => $payment->getOrderId(),
    ]);
  }

  /**
   * Handle a single subscription event.
   *
   * @param array $event
   *   Event details, see https://developer.gocardless.com/api-reference/#core-endpoints-events.
   */
  private function handleSubscriptionEvent($event) {
    $logger = \Drupal::logger('commerce_gocardless');

    $payment_id = $event['links']['payment'];
    $subscription_id = $event['links']['subscription'];

    switch ($event['action']) {
      default:
        $logger->info('Message received about subscription {sid} / payment {pid}. Action is {action}.', [
          'pid' => $payment_id,
          'sid' => $subscription_id,
          'action' => $event['action'],
        ]);
    }
  }

  /**
   * Handle an event of unknown type, by logging a warning message.
   *
   * @param array $event
   *   Event details, see https://developer.gocardless.com/api-reference/#core-endpoints-events.
   */
  private function handleUnknownEvent($event) {
    $logger = \Drupal::logger('commerce_gocardless');

    $logger->warning('Unable to handle messages for resources of type {resource_type}', [
      'resource_type' => $event['resource_type'],
    ]);
  }

}
