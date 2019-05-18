<?php

namespace Drupal\commerce_razorpay\Controller;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class Controller
 * @package Drupal\commerce_razorpay\Controller
 */
class Controller extends ControllerBase{

  public function capturePayment() {
    $amount = $_GET['amount'];
    $commerce_order_id = $_GET['order_id'];
    $payment_settings = json_decode($_GET['payment_settings']);
    $response = json_decode($_GET['response']);
    $razorpay_signature = $response->razorpay_signature;
    $razorpay_payment_id = $response->razorpay_payment_id;
    $razorpay_order_id = $response->razorpay_order_id;
    $key_id = $payment_settings->key_id;
    $key_secret = $payment_settings->key_secret;

    $api = new Api($key_id, $key_secret);
    $payment = $api->payment->fetch($razorpay_payment_id);
    if($payment->status == 'authorized') {
      $payment->capture(array('amount' => $amount));
    }
    // @TODO Save payment method details in order object.

    // Validating  Signature.
    $success = true;
    $error = "Payment Failed";

    if (empty($razorpay_payment_id) === false)
    {
      $api = new Api($key_id, $key_secret);
      try
      {
        $attributes = array(
          'razorpay_order_id' => $razorpay_order_id,
          'razorpay_payment_id' => $razorpay_payment_id,
          'razorpay_signature' => $razorpay_signature
        );
        $api->utility->verifyPaymentSignature($attributes);
      }
      catch(SignatureVerificationError $e)
      {
        $success = false;
        $error = 'Razorpay Error : ' . $e->getMessage();

      }
    }

    // If Payment is successfully captured at razorpay end.
    if ($success === true) {
      $message = "Payment ID: {$razorpay_payment_id}";
      drupal_set_message(t($message));
    }
    else {
      $message = "Your payment failed " . $error;
      drupal_set_message(t($message), 'error');
    }
    $url =  Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $commerce_order_id,
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
    return new RedirectResponse($url);

  }
}
