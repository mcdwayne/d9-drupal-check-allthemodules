<?php

namespace Drupal\commerce_mollie\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Mollie\Api\Types\PaymentStatus as MolliePaymentStatus;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Middleware that redirects to cancel or completed checkout step.
 */
class MollieReturnController extends ControllerBase {

  /**
   * Callback for commerce_mollie.checkout.mollie_return route.
   *
   * Cancelled payment is redirected to route: commerce_payment.checkout.cancel
   * Processed payment is redirected to route: commerce_payment.checkout.return
   * Non-processed payment get JsonResponse with an informative reload-message.
   */
  public function returnFromMollieMiddleware(Request $request, RouteMatchInterface $route_match) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    /** @var \Drupal\commerce_payment\PaymentStorage $payment_storage */
    $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\Payment[] $payment */
    $order_payments = $payment_storage->loadMultipleByOrder($order);
    /** @var \Drupal\commerce_payment\Entity\Payment $last_payment */
    $last_payment = end($order_payments);
    /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = \Drupal::messenger();

    // Payments that are failed go to the checkout.cancel route, but we show
    // an additional message.
    if ($last_payment->getRemoteState() === MolliePaymentStatus::STATUS_FAILED) {
      $messenger->addWarning($this->t('Your payment at Mollie has failed. You may resume the checkout process here when you are ready.'));
    }
    // Payments that are expired go to the checkout.cancel route, but we show
    // an additional message.
    if ($last_payment->getRemoteState() === MolliePaymentStatus::STATUS_EXPIRED) {
      $messenger->addWarning($this->t('Your payment at Mollie has expired. You may resume the checkout process here when you are ready.'));
    }
    // Payments that are cancelled go to the checkout.cancel route.
    $cancel_route_states = [
      MolliePaymentStatus::STATUS_CANCELED,
      MolliePaymentStatus::STATUS_FAILED,
      MolliePaymentStatus::STATUS_EXPIRED,
    ];
    if (in_array($last_payment->getRemoteState(), $cancel_route_states, TRUE)) {
      $cancel_url = Url::fromRoute('commerce_payment.checkout.cancel', [
        'commerce_order' => $order->id(),
        'step' => 'payment',
      ], ['absolute' => TRUE])->toString();
      return new RedirectResponse($cancel_url);
    }

    // Payments that are processed, always go to complete (for example paid).
    if ($order->isPaid() || $last_payment->getRemoteState() !== MolliePaymentStatus::STATUS_OPEN) {
      $return_url = Url::fromRoute('commerce_payment.checkout.return', [
        'commerce_order' => $order->id(),
        'step' => 'payment',
      ], ['absolute' => TRUE])->toString();
      return new RedirectResponse($return_url);
    }

    // If payment is not processed, show reload message.
    \Drupal::service('page_cache_kill_switch')->trigger();
    $reload_link = Link::createFromRoute($this->t('Please reload this page.'), 'commerce_mollie.checkout.mollie_return', ['commerce_order' => $order->id()])->toString();
    return [
      '#markup' => $this->t('We have not yet received the payment status from Mollie. @link', ['@link' => $reload_link]),
      '#cache' => ['max-age' => 0],
    ];

  }

}
