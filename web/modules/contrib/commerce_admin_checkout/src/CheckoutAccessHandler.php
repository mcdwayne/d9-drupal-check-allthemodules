<?php
namespace Drupal\commerce_admin_checkout;

use Drupal\commerce_cart\CartSession;
use Drupal\commerce_cart\CartSessionInterface;
use Drupal\commerce_checkout\Controller\CheckoutController;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CheckoutAccessHandler implements ContainerInjectionInterface {

  /**
   * The cart session.
   *
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $cartSession;

  /**
   * @var \Drupal\commerce_checkout\Controller\CheckoutController
   */
  protected $checkoutController;

  /**
   * CheckoutAccessHandler constructor.
   *
   * @param \Drupal\commerce_cart\CartSessionInterface $cartSession
   * @param \Drupal\commerce_checkout\Controller\CheckoutController $checkoutController
   */
  public function __construct(CartSessionInterface $cartSession, CheckoutController $checkoutController) {
    $this->cartSession = $cartSession;
    $this->checkoutController = $checkoutController;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_session'),
      CheckoutController::create($container)
    );
  }

  /**
   * Checks access for the form page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    
    $access = $this->checkoutController->checkAccess($route_match, $account);
    
    if ($access->isNeutral()) {
      $customer_check = ($account->isAuthenticated() && 
        ($account->hasPermission('access checkout as a different user') || $account->hasPermission('edit cart items during checkout')));
      $items_check = ($order->hasItems() || $account->hasPermission('edit cart items during checkout'));
      $access = AccessResult::allowedIf($customer_check)
        ->andIf(AccessResult::allowedIf($items_check))
        ->andIf(AccessResult::allowedIfHasPermission($account, 'access checkout'))
        ->addCacheableDependency($order);
    }

    return $access;
  }
}
