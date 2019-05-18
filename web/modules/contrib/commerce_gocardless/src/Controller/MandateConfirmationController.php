<?php

namespace Drupal\commerce_gocardless\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Controller\ControllerBase;
use GoCardlessPro\Core\Exception\InvalidApiUsageException;
use GoCardlessPro\Core\Exception\InvalidStateException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles the user redirection back from GoCardless in order to complete
 * the creation of a mandate.
 */
class MandateConfirmationController extends ControllerBase {

  /**
   * Completes the GoCardless redirect flow and creates a new customer
   * and mandate.
   *
   * Users will be redirected here from GoCardless, after completing address
   * and back account details.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order that the user was in the process of going through checkout
   *   before being redirected to GoCardless.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the checkout process or error message if something
   *   went wrong.
   */
  public function confirmFlow(OrderInterface $commerce_order, Request $request) {
    // We are given a flow ID from which we can obtain the correct user.
    $redirect_flow_id = $request->get('redirect_flow_id');
    if (!$redirect_flow_id) {
      drupal_set_message('No redirect_flow_id parameter was set', 'error');
      return [];
    }

    // The session also needs to match the one that we used previously.
    // We need to fill in customer details and complete in the same session.
    $session_token = $request->getSession()->getId();

    try {
      /** @var \Drupal\commerce_gocardless\Plugin\Commerce\PaymentGateway\GoCardlessPaymentGatewayInterface $payment_gateway */
      $payment_gateway = $commerce_order->payment_gateway->entity->getPlugin();
      $redirectFlow = $payment_gateway
        ->createGoCardlessClient()
        ->redirectFlows()
        ->complete($redirect_flow_id, [
          'params' => ['session_token' => $session_token],
        ]);

      // If we get here then the customer (and a mandate) was successful.
      // We get IDs for the customer and the mandate, which we should keep
      // along with the user. The flow ID is no longer relevant.
      $customer_id = $redirectFlow->links->customer;
      $mandate_id = $redirectFlow->links->mandate;

      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      $payment_method = $commerce_order->payment_method->entity;
      $payment_method->setRemoteId($mandate_id);
      // The payment method doesn't need an expiry.

      $payment_method->save();

    }
    catch (InvalidApiUsageException $e) {
      // This is probably because the redirect_flow_id parameter was wrong.
      // We have not been able to complete the flow, so there is no point
      // proceeding with the order.
      throw new NotFoundHttpException();

    }
    catch (InvalidStateException $e) {
      // The flow cannot be completed because it is not in the correct state.
      // We cannot update it, and therefore cannot obtain a mandate ID.
      // This won't be a problem if the flow has already been validated, we
      // can just continue the checkout process.
      // It is a slight problem if the flow has not been completed at all -
      // the saved payment method will have no mandate and is useless. But
      // if this happens the checkout process will see the missing mandate ID
      // and redirect the user to GoCardless again.
    }

    // Return the user to the checkout process for the order.
    // We don't need to specify the step here - the checkout process will
    // automatically handle that.
    return $this->redirect('commerce_checkout.form', [
      'commerce_order' => $commerce_order->id(),
    ]);
  }

}
