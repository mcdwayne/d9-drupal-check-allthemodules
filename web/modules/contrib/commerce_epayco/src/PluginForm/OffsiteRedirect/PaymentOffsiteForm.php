<?php

namespace Drupal\commerce_epayco\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_epayco\Entity\CommerceEpaycoApiData as ePaycoConfig;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  const EPAYCO_API_URL = 'https://secure.payco.co/checkout.php';

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = \Drupal::routeMatch()->getParameter('commerce_order');

    /** @var \Drupal\commerce_store\Entity\Store $store */
    $store = $order->getStore();

    /** @var \Drupal\commerce_epayco\Entity\CommerceEpaycoApiData $configurarion */
    $configuration = ePaycoConfig::load($payment_gateway_plugin->getConfiguration()['configuration']);

    $order_id = $order->id();
    $address = $order->getBillingProfile()->address->first();

    /*
     * This is a workaround to save the Payment Gateway ID.
     * @see Drupal\commerce_epayco\Plugin\Commerce\PaymentGateway\ePaycoRedirect::onReturn
     * @todo Check for a better way.
     */
    $payment_gateway_original_id = $payment->getPaymentGateway()->getOriginalId();
    $tempstore = \Drupal::service('user.private_tempstore')->get('commerce_epayco');
    $tempstore->set('payment_gateway', $payment_gateway_original_id);

    $store__p_cust_id_cliente = $store->get('epayco_p_cust_id_cliente')->getString();
    $store__p_key = $store->get('epayco_p_key')->getString();
    $store__mode = $store->get('epayco_mode')->getString();
    if ($store__p_cust_id_cliente && $store__p_key) {
      // Let's check if store has overriden settings.
      $__p_cust_id_cliente = $store__p_cust_id_cliente;
      $__p_key = $store__p_key;
      $__p_test_request = $store__mode == '1' ? TRUE : FALSE;
    }
    else {
      // If settings are not overriden, then use global settings.
      $__p_cust_id_cliente = $configuration->getIdClient();
      $__p_key = $configuration->getPkey();
      $__p_test_request = $configuration->isTestMode();
    }
    $__p_confirm_method = $payment_gateway_plugin->getConfiguration()['p_confirm_method'];
    $__p_amount = $payment->getAmount()->getNumber();
    $__p_currency_code = $payment->getAmount()->getCurrencyCode();
    $__p_signature = commerce_epayco_get_payment_signature($__p_cust_id_cliente, $__p_key, $order_id, $__p_amount, $__p_currency_code);
    $p_tax = 0;
    foreach ($order->collectAdjustments() as $key => $tax) {
      $p_tax += $tax->getAmount()->getNumber();
    }

    $parameters = [
      'p_cust_id_cliente' => $__p_cust_id_cliente,
      'p_key' => $__p_key,
      'p_id_invoice' => $order_id,
      'p_description' => t('Purchase order #@order_id', ['@order_id' => $order_id]),
      'p_amount' => $__p_amount,
      'p_amount_base' => $__p_amount - $p_tax,
      'p_tax' => $p_tax,
      'p_email' => $order->getEmail(),
      'p_currency_code' => $__p_currency_code,
      'p_signature' => $__p_signature,
      'p_test_request' => $__p_test_request,
      'p_customer_ip' => $order->getIpAddress(),
      'p_url_response' => $form['#return_url'],
      'p_url_confirmation' => '',
      'p_confirm_method' => $__p_confirm_method,
      'p_extra1' => '',
      'p_extra2' => '',
      'p_extra3' => '',
      'p_billing_document' => '',
      'p_billing_name' => $address->getGivenName(),
      'p_billing_lastname' => $address->getFamilyName(),
      'p_billing_address' => $address->getAddressLine1() . ' ' . $address->getAddressLine2(),
      'p_billing_country' => $address->getCountryCode(),
      'p_billing_email' => $order->getEmail(),
      'p_billing_phone' => '',
      'p_billing_cellphone' => '',
    ];

    // Allow external modules to alter payment data
    // before sending it to the gateway.
    foreach (\Drupal::moduleHandler()->getImplementations('commerce_epayco_payment_data') as $module) {
      $parameters = \Drupal::moduleHandler()->invoke($module, 'commerce_epayco_payment_data', [
        $order, $payment, $parameters,
      ]);
    }

    return $this->buildRedirectForm($form, $form_state, self::EPAYCO_API_URL, $parameters, 'post');
  }

}
