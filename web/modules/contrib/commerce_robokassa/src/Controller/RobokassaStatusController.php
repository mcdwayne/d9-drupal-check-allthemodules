<?php
namespace Drupal\commerce_robokassa\Controller;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_robokassa\Plugin\Commerce\PaymentGateway\RobokassaPayment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RobokassaStatusController extends ControllerBase {

  /**
   * Checks access for the form page.
   *
   * @param Request $request
   *  The request.
   * @param RouteMatchInterface $route_match
   *   The route.
   * @param PaymentGatewayInterface $commerce_payment_gateway
   *   The current commerce payment gateway
   * @param string status
   *   The current payment status from Robokassa
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The access result.
   */
  public function statusPage(Request $request, RouteMatchInterface $route_match, PaymentGatewayInterface $commerce_payment_gateway, $status) {
    $order_id = $request->getMethod() == 'GET' ? $request->query->get('InvId') : $request->request->get('InvId');
    $route_name = "commerce_payment.checkout.$status";
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->entityTypeManager()->getStorage('commerce_order')->load($order_id);

    if ($status == 'cancel') {
      $payment = $commerce_payment_gateway->getPlugin()->doValidatePost($request, FALSE);
      if (!$payment) {
        throw new NotFoundHttpException();
      }

      $commerce_payment_gateway->getPlugin()->setLocalState($payment, 'fail');
      $payment->save();

      $order->unlock();
      $order->save();
    }

    $step_id = $order->get('checkout_step')->getValue()[0]['value'];
    $internal_parameters = [
      'commerce_order' => $order_id,
      'step' => $step_id,
    ];

    return $this->redirect($route_name, $internal_parameters);
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
    $request = \Drupal::request();
    $order_id = $request->getMethod() == 'GET' ? $request->query->get('InvId') : $request->request->get('InvId');
    $status = $route_match->getParameter('status');
    return AccessResult::allowedIf($order_id && in_array($status, ['cancel', 'return']));
  }

}