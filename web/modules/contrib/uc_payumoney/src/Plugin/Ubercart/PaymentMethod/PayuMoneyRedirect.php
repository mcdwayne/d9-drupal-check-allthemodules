<?php

namespace Drupal\uc_payumoney\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;

/**
 * Defines the PayPal Payments Standard payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "uc_payumoney",
 *   name = @Translation("Payu Money Redirect")
 * )
 */
class PayuMoneyRedirect extends PaymentMethodPluginBase implements OffsitePaymentMethodPluginInterface {
  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $pkey = $this->configuration['pkey'];
    $psalt = $this->configuration['psalt'];
    $pmode = $this->configuration['pmode'];

    $form['pkey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Key'),
      '#default_value' => $pkey,
      '#description' => $this->t('Use "gtKFFx" as Test Key'),
      '#required' => TRUE,
    ];
    $form['psalt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Salt'),
      '#default_value' => $psalt,
      '#description' => $this->t('Use "eCwWELxi" as Test Salt'),
      '#required' => TRUE,
    ];

    $form['pmode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => array(
        'https://test.payu.in/_payment' => t('TEST'),
        'https://secure.payu.in/_payment' => t('LIVE'),
      ),
      '#default_value' => $pmode,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['pkey'] = $values['pkey'];
      $this->configuration['psalt'] = $values['psalt'];
      $this->configuration['pmode'] = $values['pmode'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $key = $this->configuration['pkey'];
    $salt = $this->configuration['psalt'];
    $txnid = $order->id();
    $address = $order->getAddress('billing');
    $firstname = substr($address->first_name, 0, 128);
    $lastname = substr($address->last_name, 0, 128);
    $address1 = substr($address->street1, 0, 64);
    $address2 = substr($address->street2, 0, 64);
//    $productinfo = $this->t('Order @order_id at @store', ['@order_id' => $order->id(), '@store' => uc_store_name()]);
    $productinfo = 'Order ' . $order->id() . ' at ' . uc_store_name();
    $amount = uc_currency_format($order->getTotal(), FALSE, FALSE, '.');
    $zipcode = substr($address->postal_code, 0, 16);
    $city = substr($address->city, 0, 64);
    $state = $address->zone;
    $country = $address->country;
    $email = substr($order->getEmail(), 0, 64);
    $phone = substr($address->phone, 0, 16);
    $surl = Url::FromRoute('uc_payumoney.complete', ['order_id' => $order->id(),], ['absolute' => TRUE])->toString();
    $pg = isset($_SESSION['pay_method']) ? $_SESSION['pay_method'] : 'CC';
    $string = $key . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' . $firstname . '|' . $email . '|||||||||||' . $salt;
    $hash = strtolower(hash('sha512', $string));
    $data = array(
      'key' => $key,
      'txnid' => $txnid,
      'service_provider' => 'payu_paisa',
      'firstname' => $firstname,
      'lastname' => $lastname,
      'address1' => $address1,
      'address2' => $address2,
      'productinfo' => $productinfo,
      'amount' => $amount,
      'zipcode' => $zipcode,
      'city' => $city,
      'state' => $state,
      'country' => $country,
      'email' => $email,
      'phone' => $phone ?: 9876543210,
      'surl' => $surl,
      'furl' => $surl,
      'curl' => $surl,
      'pg' => $pg,
      'hash' => $hash
    );
    $form['#action'] = $this->configuration['pmode'];

    foreach ($data as $name => $value) {
      if (!empty($value)) {
        $form[$name] = ['#type' => 'hidden', '#value' => $value];
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit order'),
    );
    return $form;
  }

}
