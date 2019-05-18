<?php

namespace Drupal\trustpay\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class TrustcardForm extends BasePaymentOffsiteForm {

  const REDIRECT_URL_TEST = 'https://playground.trustpay.eu/mapi5/Card/Pay';
  const REDIRECT_URL_LIVE = 'https://ib.trustpay.eu/mapi5/Card/Pay';

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $mode = $payment_gateway_plugin->getConfiguration()['mode'];
    $redirect_method = $payment_gateway_plugin->getConfiguration()['redirect_method'];
    if ($mode === '0') {
      $redirect_url = self::REDIRECT_URL_TEST;
    }
    else {
      $redirect_url = self::REDIRECT_URL_LIVE;
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    // @todo AID = Merchant account ID.
    $data['AID'] = $payment_gateway_plugin->getConfiguration()['aid'];
    // Amount of payment (2 decimal).
    $data['AMT'] = number_format($order->getTotalPrice()->getNumber(), 2, '.', '');
    // Currency.
    $data['CUR'] = $order->getTotalPrice()->getCurrencyCode();
    // Reference - internal ID (order ID).
    $data['REF'] = $order->id();

    // Return, Cancel and Error urls.
    $data['RURL'] = $form['#return_url'];
    $data['CURL'] = $form['#cancel_url'];
    $data['EURL'] = $form['#exception_url'];
    $data['NURL'] = $payment_gateway_plugin->getNotifyUrl()->toString();

    $sig_key = $payment_gateway_plugin->getConfiguration()['secret_key'];
    $sig_message = $data['AID'] . $data['AMT'] . $data['CUR'] . $data['REF'];

    $data['SIG'] = getSign($sig_key, $sig_message);
    // Customer's country
    $address_value = $order->getBillingProfile()->get('address')->getValue();
    $address = reset($address_value);

    $data['CNT'] = $address['country_code'];
    // Language.
    $data['LNG'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
    // Description.
    $data['DSC'] = t('Payment for order:') . ' ' . $order->id();
    $data['EMA'] = $order->getEmail();

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }

}
