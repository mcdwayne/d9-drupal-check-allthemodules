<?php

namespace Drupal\commerce_wayforpay\PluginForm\OffsiteRedirect;

use Drupal\commerce_wayforpay\Form\WayforpayFormTrait;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the class for payment off-site form.
 *
 * Provide a buildConfigurationForm() method which calls buildRedirectForm()
 * with the right parameters.
 */
class WayforpayPaymentForm extends BasePaymentOffsiteForm {
  use WayforpayFormTrait;

  /**
   * Gateway plugin.
   *
   * @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface
   */
  private $paymentGatewayPlugin;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
      array $form,
      FormStateInterface $form_state
  ) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $this->paymentGatewayPlugin = $payment->getPaymentGateway()->getPlugin();
    $configuration = $this->paymentGatewayPlugin->getConfiguration();
    $this->config = $configuration;
    $order = $payment->getOrder();
    $total_price = $order->getTotalPrice();
    $amount = number_format($total_price->getNumber(), '2', '.', '');

    $productName = [];
    $productPrice = [];
    $productCount = [];
    foreach ($order->getItems() as $i) {
      $productName[] = $i->getTitle();
      $productPrice[] = number_format($i->getTotalPrice()->getNumber(), 2, '.',
        '');
      $productCount[] = (int) $i->getQuantity();
    }
    $billing_profile = $order->getBillingProfile();
    $address = $billing_profile->get('address')->getValue()[0];
    $merchant_data['returnUrl'] = $form['#return_url'];
    $merchant_data['serviceUrl'] = Url::fromRoute('commerce_payment.notify',
      ['commerce_payment_gateway' => $payment->getPaymentGatewayId()],
      ['absolute' => TRUE])->toString();
    $merchant_data['merchantTransactionSecureType'] = 'NON3DS';
    $merchant_data['amount'] = $amount;
    $merchant_data['clientFirstName'] = $address['given_name'];
    $merchant_data['clientLastName'] = $address['family_name'];
    $merchant_data['clientEmail'] = $order->getEmail();
    $merchant_data['currency'] = $total_price->getCurrencyCode();
    $merchant_data['merchantAuthType'] = 'SimpleSignature';
    $merchant_data['merchantTransactionType'] = 'SALE';
    $merchant_data['merchantTransactionSecureType'] = 'AUTO';
    $merchant_data['orderDate'] = $order->get('created')->getValue()[0]['value'];
    $merchant_data['orderReference'] = $payment->getOrderId();
    $merchant_data['productName'] = $productName;
    $merchant_data['productPrice'] = $productPrice;
    $merchant_data['productCount'] = $productCount;

    foreach ($productName as $idx => $v) {
      $merchant_data["productName[{$idx}]"] = $productName[$idx];
      $merchant_data["productPrice[{$idx}]"] = $productPrice[$idx];
      $merchant_data["productCount[{$idx}]"] = $productCount[$idx];
    }
    foreach ($configuration as $k => $v) {
      if ($k === 'secretKey') {
        continue;
      }
      if (isset($merchant_data[$k])) {
        continue;
      }
      else {
        $merchant_data[$k] = $v;
      }
    }
    $merchant_data['merchantSignature'] = $this->makeSignature($merchant_data);
    $this->validatePaymentForm($merchant_data);
    unset($merchant_data['productName']);
    unset($merchant_data['productPrice']);
    unset($merchant_data['productCount']);
    $redirect_form = $this->buildRedirectForm($form, $form_state,
      'https://secure.wayforpay.com/pay',
      $merchant_data, self::REDIRECT_POST);
    $redirect_form['form_build_id']['#access'] = FALSE;
    $redirect_form['form_token']['#access'] = FALSE;
    $redirect_form['form_id']['#access'] = FALSE;
    return $redirect_form;
  }

}
