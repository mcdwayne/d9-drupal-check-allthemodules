<?php

namespace Drupal\commerce_paypal\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_paypal\CheckoutSdkFactoryInterface;
use Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\CheckoutInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PayPal checkout controller.
 */
class CheckoutController extends ControllerBase {

  /**
   * The PayPal Checkout SDK factory.
   *
   * @var \Drupal\commerce_paypal\CheckoutSdkFactoryInterface
   */
  protected $checkoutSdkFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  protected $entityTypeManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface $logger
   */
  protected $logger;

  /**
   * Constructs a PayPalCheckoutController object.
   *
   * @param \Drupal\commerce_paypal\CheckoutSdkFactoryInterface $checkout_sdk_factory
   *   The PayPal Checkout SDK factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(CheckoutSdkFactoryInterface $checkout_sdk_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->checkoutSdkFactory = $checkout_sdk_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_paypal.checkout_sdk_factory'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.commerce_paypal')
    );
  }

  /**
   * Create/update the order in PayPal.
   *
   * @param PaymentGatewayInterface $commerce_payment_gateway
   *   The payment gateway.
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response.
   */
  public function onCreate(PaymentGatewayInterface $commerce_payment_gateway, OrderInterface $commerce_order) {
    if (!$commerce_payment_gateway->getPlugin() instanceof CheckoutInterface) {
      throw new AccessException('Invalid payment gateway provided.');
    }
    $order_payment_gateway_plugin = FALSE;
    if (!$commerce_order->get('payment_gateway')->isEmpty()) {
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
      $payment_gateway = $commerce_order->get('payment_gateway')->entity;
      $order_payment_gateway_plugin = $payment_gateway->getPlugin();
    }
    // The reference to the payment_gateway is required by the
    // on approve route.
    if (!$order_payment_gateway_plugin instanceof CheckoutInterface) {
      $commerce_order->set('payment_gateway', $commerce_payment_gateway);
      $commerce_order->save();
    }
    $config = $commerce_payment_gateway->getPluginConfiguration();
    $sdk = $this->checkoutSdkFactory->get($config);
    try {
      $response = $sdk->createOrder($commerce_order);
      $body = Json::decode($response->getBody()->getContents());
      return new JsonResponse(['id' => $body['id']]);
    }
    catch (ClientException $exception) {
      $this->logger->error($exception->getMessage());
      return new Response('', Response::HTTP_BAD_REQUEST);
    }
  }

  /**
   * React to the PayPal checkout "onApprove" JS SDK callback.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response.
   */
  public function onApprove(OrderInterface $commerce_order, Request $request) {
    $order = $commerce_order;
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $order->get('payment_gateway')->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    if (!$payment_gateway_plugin instanceof CheckoutInterface) {
      throw new AccessException('Unsupported payment gateway provided.');
    }
    $body = Json::decode($request->getContent());
    if (!isset($body['flow']) || !in_array($body['flow'], ['mark', 'shortcut'])) {
      throw new AccessException('Unsupported flow.');
    }
    try {
      // Note that we're using a custom route instead of the payment return
      // one since the payment return callback cannot be called from the cart
      // page.
      $payment_gateway_plugin->onReturn($order, $request);
      $step_id = $order->get('checkout_step')->value;

      // Redirect to the next checkout step, the current checkout step is
      // known, which isn't the case when in the "shortcut" flow.
      if (!empty($step_id)) {
        /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
        $checkout_flow = $order->get('checkout_flow')->entity;
        $checkout_flow_plugin = $checkout_flow->getPlugin();
        $redirect_step_id = $checkout_flow_plugin->getNextStepId($step_id);
        $order->set('checkout_step', $redirect_step_id);
      }
      $order->save();
      $redirect_url = Url::fromRoute('commerce_checkout.form', [
        'commerce_order' => $order->id(),
        'step' => $step_id,
      ])->toString();
      return new JsonResponse(['redirectUrl' => $redirect_url]);
    }
    catch (PaymentGatewayException $e) {
      // When the payment fails, we don't instruct the JS to redirect, the page
      // will be reloaded to show errors.
      $this->logger->error($e->getMessage());
      $this->messenger->addError(t('Payment failed at the payment server. Please review your information and try again.'));
    }
  }

}
