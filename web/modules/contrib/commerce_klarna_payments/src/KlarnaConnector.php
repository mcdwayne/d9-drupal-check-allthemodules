<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments;

use Drupal\commerce_klarna_payments\Event\Events;
use Drupal\commerce_klarna_payments\Event\RequestEvent;
use Drupal\commerce_klarna_payments\Klarna\AuthorizationResponse;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface;
use Drupal\commerce_klarna_payments\Klarna\Exception\FraudException;
use Drupal\commerce_klarna_payments\Klarna\Rest\Authorization;
use Drupal\commerce_klarna_payments\Klarna\Rest\Session;
use Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway\Klarna;
use Drupal\commerce_order\Entity\OrderInterface;
use GuzzleHttp\Exception\ClientException;
use Klarna\Rest\OrderManagement\Order;
use Klarna\Rest\Payments\Orders;
use Klarna\Rest\Payments\Sessions;
use Klarna\Rest\Transport\Connector;
use Klarna\Rest\Transport\Exception\ConnectorException;
use Klarna\Rest\Transport\UserAgent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a service to interact with Klarna.
 */
final class KlarnaConnector {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventSubscriberInterface
   */
  protected $eventDispatcher;

  /**
   * The connector.
   *
   * @var \Klarna\Rest\Transport\Connector
   */
  protected $connector;

  /**
   * Constructs a new instance.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Klarna\Rest\Transport\Connector|null $connector
   *   The connector.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher, Connector $connector = NULL) {
    $this->eventDispatcher = $eventDispatcher;
    $this->connector = $connector;
  }

  /**
   * Gets the connector.
   *
   * @param \Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway\Klarna $plugin
   *   The plugin.
   *
   * @return \Klarna\Rest\Transport\Connector
   *   The connector.
   */
  protected function getConnector(Klarna $plugin) : Connector {
    if (!$this->connector) {
      $ua = (new UserAgent())
        ->setField('Library', 'drupal-klarna-payments-v1');

      // Populate default connector.
      $this->connector = Connector::create(
        $plugin->getUsername(),
        $plugin->getPassword(),
        $plugin->getApiUri(),
        $ua
      );
    }
    return $this->connector;
  }

  /**
   * Gets the Klarna session.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Klarna\Rest\Payments\Sessions
   *   The Klarna session.
   */
  protected function getSession(OrderInterface $order) : Sessions {
    $plugin = $this->getPlugin($order);
    // Attempt to use already stored session id.
    $sessionId = $order->getData('klarna_session_id', NULL);

    return new Sessions($this->getConnector($plugin), $sessionId);
  }

  /**
   * Gets the authorize request.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface
   *   The request.
   */
  public function authorizeRequest(OrderInterface $order) : RequestInterface {
    /** @var \Drupal\commerce_klarna_payments\Event\RequestEvent $event */
    $event = $this->eventDispatcher
      ->dispatch(Events::ORDER_CREATE, new RequestEvent($order));

    return $event->getRequest();
  }

  /**
   * Gets the plugin for given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway\Klarna
   *   The payment plugin.
   */
  public function getPlugin(OrderInterface $order) : Klarna {
    $gateway = $order->get('payment_gateway');

    if ($gateway->isEmpty()) {
      throw new \InvalidArgumentException('Payment gateway not found.');
    }
    $plugin = $gateway->first()->entity->getPlugin();

    if (!$plugin instanceof Klarna) {
      throw new \InvalidArgumentException('Payment gateway not instanceof Klarna.');
    }
    return $plugin;
  }

  /**
   * Gets the Klarna order for given order.
   *
   * @todo Create response value object.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Klarna\Rest\OrderManagement\Order
   *   The klarna order.
   */
  public function getOrder(OrderInterface $order) : Order {
    $plugin = $this->getPlugin($order);

    if (!$orderId = $order->getData('klarna_order_id')) {
      throw new \InvalidArgumentException('Klarna order ID is not set.');
    }

    try {
      $order = new Order($this->getConnector($plugin), $orderId);
      $order->fetch();

      if (!isset($order['status'])) {
        throw new \InvalidArgumentException('Order status not found.');
      }
    }
    catch (ConnectorException $e) {
      throw new \InvalidArgumentException($e->getMessage());
    }

    return $order;
  }

  /**
   * Authorizes the order.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface $request
   *   The request.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $authorizeToken
   *   The authorize token.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\AuthorizationResponse
   *   The authorization response.
   *
   * @throws \Drupal\commerce_klarna_payments\Klarna\Exception\FraudException
   */
  public function authorizeOrder(RequestInterface $request, OrderInterface $order, string $authorizeToken) : AuthorizationResponse {
    $build = $request->toArray();

    $plugin = $this->getPlugin($order);
    $authorizer = new Orders($this->getConnector($plugin), $authorizeToken);
    $data = $authorizer->create($build);

    if (!isset($data['fraud_status'], $data['redirect_url'], $data['order_id'])) {
      throw new \InvalidArgumentException('Authorization validation failed.');
    }

    $response = AuthorizationResponse::createFromArray($data);

    if (!$response->isAccepted() && !$response->isPending()) {
      throw new FraudException('Fraud validation failed.');
    }
    $order->setData('klarna_order_id', $response->getOrderId())
      ->save();

    return $response;
  }

  /**
   * Gets the session request.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface
   *   The request.
   */
  public function sessionRequest(OrderInterface $order) : RequestInterface {
    /** @var \Drupal\commerce_klarna_payments\Event\RequestEvent $event */
    $event = $this->eventDispatcher
      ->dispatch(Events::SESSION_CREATE, new RequestEvent($order));

    return $event->getRequest();
  }

  /**
   * Builds a new order transaction.
   *
   * @todo Create response value object.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface $request
   *   The request.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return array
   *   The Klarna request data.
   */
  public function buildTransaction(RequestInterface $request, OrderInterface $order) : array {
    $build = $request->toArray();

    $session = $this->getSession($order);

    // Create new session if one doesn't exist already.
    if (!isset($session['session_id'])) {
      $session->create($build);
    }
    else {
      try {
        $session->update($build);
        $session->fetch();
      }
      catch (ConnectorException | ClientException $e) {
        // Attempt to create new session session if update failed.
        // This usually happens when we have an expired session id
        // saved in commerce order.
        $session->create($build);
      }
    }
    // Session id will be reset when we update the session and
    // re-fetch. Update only if session id has changed.
    if (!empty($session['session_id'])) {
      $order->setData('klarna_session_id', $session['session_id'])
        ->save();
    }
    return $build + [
      'client_token' => $session['client_token'],
      'payment_method_categories' => $session['payment_method_categories'] ?? [],
    ];
  }

}
