<?php

namespace Drupal\uc_paymentexpress\Controller;

use Drupal\uc_paymentexpress\DPSApi\PxPay_Curl;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\uc_payment\Entity\PaymentMethod;
use Drupal\uc_order\Entity\Order;

/**
 * Returns responses for DPS routes.
 */
class DPSController extends ControllerBase {

  protected $DpsMethodInstanceEntityId;

  /**
   * Handles the review page for DPS Checkout Flow.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart complete page.
   */
  public function dpsReviewRedirect() {
    $return_info = \Drupal::request()->get('result', FALSE);

    // EMERGENCY ALERT CRITICAL ERROR WARNING NOTICE INFO DEBUG.
    \Drupal::logger('uc_paymentexpress')->info('Process DPS payment response');

    if ($return_info) {
      $pxpay = $this->getPaymentConfig();

      $enc_hex = $return_info;
      $rsp = $pxpay->getResponse($enc_hex);

      $result = new \stdClass();
      // =1 when request succeeds.
      $result->success           = $rsp->getSuccess();
      // $result->retry             = $rsp->getRetry();
      // =1 when a retry might help
      // $result->statusrequired    = $rsp->getStatusRequired();
      // =1 when transaction "lost".
      $result->amountsettlement  = $rsp->getAmountSettlement();
      // From bank.
      $result->authcode          = $rsp->getAuthCode();
      // e.g. "Visa".
      $result->cardname          = $rsp->getCardName();
      $result->dpstxnref         = $rsp->getDpsTxnRef();

      // The following values are returned, but are from the original request.
      $result->txntype           = $rsp->getTxnType();
      $result->txndata1          = $rsp->getTxnData1();
      $result->txndata2          = $rsp->getTxnData2();
      $result->txndata3          = $rsp->getTxnData3();
      $result->currencyinput     = $rsp->getCurrencyInput();
      $result->emailaddress      = $rsp->getEmailAddress();
      $result->txnid             = $rsp->getMerchantReference();
      $result->dpsbillingid      = $rsp->getDpsBillingId();
      $result->dateexpiry        = $rsp->getDateExpiry();

      $order = Order::load($result->txnid);
      if (!$order) {
        // $config = \Drupal::config('system.site');.
        \Drupal::logger('uc_paymentexpress')->error('DPS attempted for non-existent order @order_id.', ['@order_id' => $result->txnid]);
        throw new NotFoundHttpException();
      }

      // EMERGENCY ALERT CRITICAL ERROR WARNING NOTICE INFO DEBUG.
      \Drupal::logger('uc_paymentexpress')->info('Receiving new order notification for order @order_id.', array('@order_id' => $result->txnid));

      if ($result->success == "1") {
        $comment = $this->t('DPS PXPay Auth code: @auth_code, Transaction ID: @txn_id', array(
          '@auth_code' => $result->authcode,
          '@txn_id'    => $result->dpstxnref,
        ));

        $payments = uc_payment_load_payments($result->txnid);

        /*
         * Check for duplicate transaction.
         * DPS sends FPRN and also returns browser
         * with data to validate transaction,
         * so we are likely to see both.
         * Both notifications should set the payment
         * for the order, but only once.
         * The browser return should fire
         * uc_cart_complete_sale() to log in if reqd.
         */
        $duplicate_flag = FALSE;
        if (!empty($payments)) {
          foreach ($payments as $payment) {
            if ($payment->comment == $comment) {
              $duplicate_flag = TRUE;
            }
          }
        }

        if (!$duplicate_flag) {
          /*
          seems the data have some extra layer that not took
          array init and then [key] value set, serializ function in the middle?
          $order->data->dps_pac = array();
          $order->data->dps_pac['dpsbillingid'] = $result->dpsbillingid;
          $order->data->dps_pac['dateexpiry'] = $result->dateexpiry;
           */
          $order->data->dps_pac = array('dpsbillingid' => $result->dpsbillingid, 'dateexpiry' => $result->dateexpiry);
          $order->save();

          $order_status = $order->getStatusId();
          if ($order_status && $order_status == 'in_checkout') {
            $order->setStatusId('payment_received')->save();
          }

          uc_payment_enter($result->txnid, $this->DpsMethodInstanceEntityId, $result->amountsettlement, $order->getOwnerId(), $order->data->payment_details, $comment);

          uc_order_comment_save($result->txnid, 0, $this->t('DPS PXPay payment successful'), 'order', 'payment_received');

          uc_order_comment_save($result->txnid, 0, $this->t('PXPay reported a payment of @amount @currency.', array(
            '@amount' => uc_currency_format($result->amountsettlement, FALSE),
            '@currency' => $order->getCurrency(),
          )));

          \Drupal::logger('uc_paymentexpress')->notice('Order @order_id transaction was completed!', ['@order_id' => $result->txnid]);
        }
        else {
          \Drupal::logger('uc_paymentexpress')->warning('Order @order_id ignore duplicate dps triger!!', ['@order_id' => $result->txnid]);
        }

        $session = \Drupal::service('session');
        $complete = array();
        if ($session->has('uc_checkout')) {
          $complete = $session->get('uc_checkout');
        }
        $complete[$session->get('cart_order')]['do_complete'] = TRUE;
        $session->set('uc_checkout', $complete);
      }
      else {
        // @todo Put in some more check / redirection late on.
        drupal_set_message($this->t('An error occurred while processing your order, please make sure the card detail is correct.'), 'error');
      }
    }
    else {
      \Drupal::logger('uc_paymentexpress')->error('DPS process page called without pass in var.');
      throw new NotFoundHttpException();
    }

    $process_path = Url::fromRoute('uc_cart.checkout_complete', [], array('absolute' => TRUE))->toString();
    return new RedirectResponse($process_path);
  }

  /**
   * Handles the failed page for DPS Checkout Flow.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart review page.
   */
  public function dpsFailedRedirect() {
    drupal_set_message($this->t('An error occurred while processing your order, please make sure the card detail is correct.'), 'error');
    $process_path = Url::fromRoute('uc_cart.checkout_review', [], array('absolute' => TRUE))->toString();
    return new RedirectResponse($process_path);
  }

  /**
   * Function to load the created payment config.
   */
  protected function getPaymentConfig() {
    $pxpay = FALSE;
    $methods = PaymentMethod::loadMultiple();
    uasort($methods, 'Drupal\uc_payment\Entity\PaymentMethod::sort');
    foreach ($methods as $method) {
      $temp_plugin_entity_config = $method->getPlugin();
      if ($temp_plugin_entity_config && $temp_plugin_entity_config->getPluginId() == 'dps') {
        $dps_settings = $temp_plugin_entity_config->getConfiguration();
        if ($dps_settings) {
          $this->DpsMethodInstanceEntityId = $method->id();
          if ($dps_settings['dps_test_mod']) {
            $pxpay = new PxPay_Curl($dps_settings['px_access_test_url'], $dps_settings['px_access_test_uid'], $dps_settings['px_access_test_key']);
          }
          else {
            $pxpay = new PxPay_Curl($dps_settings['px_access_url'], $dps_settings['px_access_uid'], $dps_settings['px_access_key']);
          }
          break;
        }
      }
    }
    return $pxpay;
  }

}
