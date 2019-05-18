<?php

namespace Drupal\commerce_amazon_lpa\Controller;

use Drupal\commerce_cart\CartSession;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for an Amazon checkout.
 */
class AmazonPayCheckout extends ControllerBase {

  /**
   * Checkout route controller.
   */
  public function handleOrder(Request $request) {
    $order_id = $request->query->get('order_id');
    $carts = \Drupal::getContainer()->get('commerce_cart.cart_provider')->getCarts();
    $active_cart = array_filter($carts, function (OrderInterface $cart) use ($order_id) {
      return $cart->id() == $order_id;
    });

    if (empty($active_cart)) {
      return new RedirectResponse(Url::fromRoute('commerce_cart.page')->toString());
    }

    $amazon_pay = \Drupal::getContainer()->get('commerce_amazon_lpa.amazon_pay');
    if ($request->query->has('access_token')) {
      $access_token = $request->query->get('access_token');
    }
    elseif ($request->cookies->has('amazon_Login_accessToken')) {
      $access_token = $request->cookies->get('amazon_Login_accessToken');
    }
    else {
      return new RedirectResponse(Url::fromRoute('commerce_cart.page')->toString());
    }
    $user_information = $amazon_pay->getUserInfo($access_token);
    if (empty($user_information)) {
      return new RedirectResponse(Url::fromRoute('commerce_cart.page')->toString());
    }
    /** @var \Drupal\commerce_order\Entity\OrderInterface $active_cart */
    $active_cart = reset($active_cart);

    if ($this->config('commerce_amazon_lpa.settings')->get('operation_mode') == 'pay_lwa') {
      if ($this->currentUser()->isAnonymous()) {
        // @todo Log in user / create as \Drupal\commerce_amazon_lpa\Controller\LoginWithAmazon::handleLoginRedirect.
      }
    }

    // @see \Drupal\commerce_checkout\Controller\CheckoutController::checkAccess
    if ($active_cart->getState()->value == 'canceled') {
      return new RedirectResponse(Url::fromRoute('commerce_cart.page')->toString());
    }

    // The user can checkout only their own non-empty orders.
    if ($this->currentUser()->isAuthenticated()) {
      $customer_check = $this->currentUser()->id() == $active_cart->getCustomerId();
    }
    else {
      $cart_session = \Drupal::getContainer()->get('commerce_cart.cart_session');
      $current_cart = $cart_session->hasCartId($active_cart->id(), CartSession::ACTIVE);
      $completed_cart = $cart_session->hasCartId($active_cart->id(), CartSession::COMPLETED);
      $customer_check = $current_cart || $completed_cart;
    }

    if ($customer_check && $active_cart->hasItems()) {
      $active_cart->setEmail($user_information['email']);
      $active_cart->get('checkout_flow')->setValue(NULL);
      $active_cart->get('checkout_step')->setValue(NULL);
      $active_cart->get('payment_gateway')->setValue('amazon_pay');

      // Check if the order is re-entering Amazon checkout, and that is has
      // a valid state.
      if (!$active_cart->get('amazon_order_reference')->isEmpty()) {
        // @todo fetch order reference. If it is not draft, remove field value.
      }
      else {
        // Set a temporal reference ID so the checkout resolver works.
        $mode = $this->config('commerce_amazon_lpa.settings')->get('mode');
        $active_cart->get('amazon_order_reference')->setValue($mode == 'test' ? 'S' : 'P');
      }
      $active_cart->save();

      return new RedirectResponse(Url::fromRoute('commerce_checkout.form', [
        'commerce_order' => $active_cart->id(),
      ])->toString());
    }

    return new RedirectResponse(Url::fromRoute('commerce_cart.page')->toString());
  }

}
