<?php

namespace Drupal\contacts_events\Controller;

use Drupal\commerce_checkout\Controller\CheckoutController;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Booking checkout process controller.
 */
class BookingCheckoutController extends CheckoutController {

  /**
   * {@inheritdoc}
   */
  public function formPage(RouteMatchInterface $route_match) {
    // Overridden so we can use our custom route instead
    // of the default commerce_checkout.form route.
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    $requested_step_id = $route_match->getParameter('step');
    $step_id = $this->checkoutOrderManager->getCheckoutStepId($order, $requested_step_id);
    if ($requested_step_id != $step_id) {
      $url = Url::fromRoute('booking_flow', ['commerce_order' => $order->id(), 'step' => $step_id]);
      return new RedirectResponse($url->toString());
    }
    $checkout_flow = $this->checkoutOrderManager->getCheckoutFlow($order);
    $checkout_flow_plugin = $checkout_flow->getPlugin();

    return $this->formBuilder->getForm($checkout_flow_plugin, $step_id);
  }

  /**
   * Title callback for the booking process.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order being checked out.
   *
   * @return string
   *   The page title.
   */
  public function checkoutTitle(OrderInterface $commerce_order) {
    // Use the event title if we can.
    if ($commerce_order->hasField('event')) {
      if ($event = $commerce_order->get('event')->entity) {
        return new FormattableMarkup('@event: @booking', [
          '@event' => $event->label(),
          '@booking' => $commerce_order->getOrderNumber(),
        ]);
      }
    }

    // Otherwise just the order number.
    return $commerce_order->getOrderNumber();
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');

    // The user can checkout their own order.
    return AccessResult::allowedIf($account->isAuthenticated() && $account->id() == $order->getCustomerId())
      ->andIf(AccessResult::allowedIfHasPermission($account, 'can book for contacts_events'))
      ->addCacheableDependency($order)
      ->addCacheableDependency($account)
      // The user can manage bookings for any order.
      ->orIf(AccessResult::allowedIfHasPermission($account, 'can manage bookings for contacts_events'));
  }

}
