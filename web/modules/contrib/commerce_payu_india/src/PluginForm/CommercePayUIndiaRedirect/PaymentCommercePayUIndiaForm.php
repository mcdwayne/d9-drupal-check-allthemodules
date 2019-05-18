<?php

namespace Drupal\commerce_payu_india\PluginForm\CommercePayUIndiaRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\Order;

/**
 * Provides the Off-site payment form.
 */
class PaymentCommercePayUIndiaForm extends BasePaymentOffsiteForm {

  const PAYUINDIA_API_TEST_URL = 'https://sandboxsecure.payu.in/_payment';
  const PAYUINDIA_API_URL = 'https://secure.payu.in/_payment';

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

    $order_id = \Drupal::routeMatch()->getParameter('commerce_order')->id();
    $order = Order::load($order_id);
    $key = $payment_gateway_plugin->getConfiguration()['key'];
    $salt = $payment_gateway_plugin->getConfiguration()['salt'];
    $mode = $payment_gateway_plugin->getConfiguration()['payment_mode'];

    $billing_profile = $order->getBillingProfile();
    $address = $order->getBillingProfile()->address->first();
    $amount = round($payment->getAmount()->getNumber(), 2);
    $txnid = $order_id;
    $hash_data['key'] = $key;
    $hash_data['txnid'] = $txnid;
    $hash_data['amount'] = $amount;
    $hash_data['productinfo'] = 'ProductorderID' . $order_id;
    $hash_data['firstname'] = $address->getGivenName();
    $hash_data['email'] = $order->getEmail();
    $service = \Drupal::service('commerce_payu_india.calculate_hash');
    $hash = $service->commercePayUIndiaGetHash($hash_data, $salt);
    // Prepare the payments parameters.
    $parameters = [
      'hash' => $hash,
      'key' => $key,
      'hash' => $hash,
      'txnid' => $txnid,
      'amount' => $amount,
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
      'service_provider' => 'payu_paisa',
    ];
    if ($mode == 'test') {
      $redirect_url = self::PAYUINDIA_API_TEST_URL;
    }
    else {
      $redirect_url = self::PAYUINDIA_API_URL;
    }
    return $this->buildRedirectForm($form, $form_state, $redirect_url, $parameters, $redirect_method);
  }

}
