<?php

namespace Drupal\commerce_partpay\Controller;

use Drupal\commerce_checkout\CheckoutOrderManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class OffSitePaymentController.
 *
 * @package Drupal\commerce_partpay\Controller
 */
class OffSitePaymentController implements ContainerInjectionInterface {

  /**
   * The checkout order manager.
   *
   * @var \Drupal\commerce_checkout\CheckoutOrderManagerInterface
   */
  protected $checkoutOrderManager;

  /**
   * The checkout order manager.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Constructs a new CheckoutController object.
   */
  public function __construct(CheckoutOrderManager $checkout_order_manager, RequestStack $request) {
    $this->checkoutOrderManager = $checkout_order_manager;
    $this->request = $request->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_checkout.checkout_order_manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Provides the "notify" page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function notifyPage(RouteMatchInterface $route_match) {

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');

    $payment_gateway_plugin = $order->payment_gateway->entity->getPlugin();

    $response = $payment_gateway_plugin->onNotify($this->request);

    if (!$response) {
      $response = new Response('', 200);
    }

    return $response;
  }

  /**
   * Checks access for the form page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match) {

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');

    if ($order->getState()->value == 'canceled') {
      return AccessResult::forbidden()->addCacheableDependency($order);
    }

    if (is_null($this->request->get('result'))) {
      return AccessResult::forbidden()->addCacheableDependency($order);
    }

    if ($order->payment_gateway->entity->id() !== 'partpay') {
      return AccessResult::forbidden()->addCacheableDependency($order);
    }

    return AccessResult::allowed();

  }

}
