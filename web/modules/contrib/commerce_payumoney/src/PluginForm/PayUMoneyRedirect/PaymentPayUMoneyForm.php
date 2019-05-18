<?php

namespace Drupal\commerce_payumoney\PluginForm\PayUMoneyRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\Order;

/**
 * Provides the Off-site payment form.
 */
class PaymentPayUMoneyForm extends BasePaymentOffsiteForm {

  const PAYUMONEY_API_TEST_URL = 'https://test.payu.in/_payment';
  const PAYUMONEY_API_URL = 'https://secure.payu.in/_payment';

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $redirect_method = 'post';
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    //$owner = \Drupal::routeMatch()->getParameter('commerce_order')->getCustomer();
    $order_id = \Drupal::routeMatch()->getParameter('commerce_order')->id();
    $order = Order::load($order_id);
    $key = $payment_gateway_plugin->getConfiguration()['pkey'];
    $salt = $payment_gateway_plugin->getConfiguration()['psalt'];
    $mode = $payment_gateway_plugin->getConfiguration()['pmode'];

    if ($mode == 'test') {
     $service_provider = '';
    }
    else {
     $service_provider = 'payu_paisa';
    }
    
    $billing_profile = $order->getBillingProfile();
    $address = $order->getBillingProfile()->address->first();
    // Prepare the payments parameters.
    $parameters = [
      'key' => $key,
      'hash' => '',
      'txnid' => substr(hash('sha256', mt_rand() . microtime()), 0, 20),
      'amount' => round($payment->getAmount()->getNumber(), 2),
      'productinfo' => 'ProductorderID' . $order_id,
      'firstname' => $address->getGivenName(),
      'lastname' => $address->getFamilyName(),
      'address1' => $address->getAddressLine1(),
      'address2' => '',
      'city' => $address->getLocality(),
      'state' => $address->getAdministrativeArea(),
      'country' => $address->getCountryCode(),
      'zipcode' => $address->getPostalCode(),
      'email' => $order->getEmail(),
      'phone' => $billing_profile->get('field_phone')->value,
      'udf1' => '',
      'udf2' => '',
      'udf3' => '',
      'udf4' => '',
      'udf5' => '',
      'udf6' => '',
      'udf7' => '',
      'udf8' => '',
      'udf9' => '',
      'udf10' => '',
      'surl' => Url::FromRoute('commerce_payment.checkout.return', ['commerce_order' => $order_id, 'step' => 'payment'], ['absolute' => TRUE])->toString(),
      'furl' => Url::FromRoute('commerce_payment.checkout.cancel', ['commerce_order' => $order_id, 'step' => 'payment'], ['absolute' => TRUE])->toString(),
      'service_provider' => $service_provider,
    ];

    // Hash Sequence.
    $hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
    $hashVarsSeq = explode('|', $hashSequence);
    $hash_string = '';
    foreach ($hashVarsSeq as $hash_var) {
      $hash_string .= isset($parameters[$hash_var]) ? $parameters[$hash_var] : '';
      $hash_string .= '|';
    }
    $hash_string .= $salt;
    $hash = strtolower(hash('sha512', $hash_string));

    $parameters['hash'] = !empty($hash) ? $hash : '';

    if ($mode == 'test') {
      $redirect_url = self::PAYUMONEY_API_TEST_URL;
    }
    else {
      $redirect_url = self::PAYUMONEY_API_URL;
    }
    return $this->buildRedirectForm($form, $form_state, $redirect_url, $parameters, $redirect_method);
  }

}
