<?php

namespace Drupal\uc_ccavenue\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;

/**
 * Defines the CCAvenue payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "ccavenue",
 *   name = @Translation("CCAvenue Payment Gateway")
 * )
 */
class CcavenuePayment extends PaymentMethodPluginBase implements OffsitePaymentMethodPluginInterface {

 // protected $ccavenue_working_key, $ccavenue_merchant_id, $ccavenue_access_code, $ccavenue_server;
  /**
   * Returns the set of card types which are used by this payment method.
   *
   * @return array
   *   An array with keys as needed by the chargeCard() method and values
   *   that can be displayed to the customer.
   */
   
  protected function getEnabledTypes() {
    return [
      'visa' => $this->t('Visa'),
      'mastercard' => $this->t('MasterCard'),
      'discover' => $this->t('Discover'),
      'amex' => $this->t('American Express'),
      'echeck' => $this->t('eCheck'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {

    $build['label'] = array(
      '#prefix' => ' ',
      '#plain_text' => $this->t('CCAvenue Payment Gateway'),
      '#suffix' => '<br /> ',
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ccavenue_working_key' => '',
      'ccavenue_merchant_id' => '',
      'ccavenue_access_code' => '',
      'ccavenue_server' => 'https://test.ccavenue.com',

    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['ccavenue_working_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Working Key'),
      '#default_value' => $this->configuration['ccavenue_working_key'],
    );
    $form['ccavenue_merchant_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['ccavenue_merchant_id'],
    );
    $form['ccavenue_access_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Access Code'),
      '#default_value' => $this->configuration['ccavenue_access_code'],
    );
    $form['ccavenue_server'] = array(
      '#type' => 'select',
      '#title' => $this->t('CCAvenue server'),
      '#options' => array(
        'https://test.ccavenue.com' => ('Test'),
        'https://secure.ccavenue.com' => ('Live'),
      ),
      '#default_value' => $this->configuration['ccavenue_server'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['ccavenue_working_key'] = trim($form_state->getValue('ccavenue_working_key'));
		$this->configuration['ccavenue_merchant_id'] = trim($form_state->getValue('ccavenue_merchant_id'));
		$this->configuration['ccavenue_access_code'] = trim($form_state->getValue('ccavenue_access_code'));
    $this->configuration['ccavenue_server'] = $form_state->getValue('ccavenue_server');
  }

  /**
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {

    $billing_address = $order->getAddress('billing');
    $delivery_address = $order->getAddress('delivery');

		$country_manager = \Drupal::service('country_manager');
    // getCountry(): Verify the basic get country config entity works.
    $b_country = $country_manager->getCountry($billing_address->country)->get('name');

    $data = array(
      // Display information.
      'cancel_url' => Url::fromRoute('uc_cart.checkout_review', [], ['absolute' => TRUE])->toString(),
      'redirect_url' => Url::fromRoute('uc_ccavenue.order_complete', ['uc_order' => $order->id()], ['absolute' => TRUE])->toString(),
      'order_id' => $order->id(),
      // Transaction information.
      'currency' => 'INR',
      'language' => 'EN',

      // Billing information.
      'billing_name' => substr($billing_address->first_name, 0, 32).' '.substr($billing_address->last_name, 0, 64),
      'billing_address' => substr($billing_address->street1, 0, 100).' '.substr($billing_address->street2, 0, 100),
      'billing_city' => substr($billing_address->city, 0, 40),      
      'billing_email' => $order->getEmail(),      
      'billing_state' => $billing_address->zone,
      'billing_zip' => $billing_address->postal_code,
      'billing_country' => $b_country,

      // Billing information.
      'delivery_name' => substr($delivery_address->first_name, 0, 32).' '.substr($delivery_address->last_name, 0, 64),
      'delivery_address' => substr($delivery_address->street1, 0, 100).' '.substr($delivery_address->street2, 0, 100),
      'delivery_city' => substr($delivery_address->city, 0, 40),      
      'delivery_state' => $delivery_address->zone,
      'delivery_zip' => $delivery_address->postal_code,
      'delivery_country' => $delivery_address->country,
    );

  
    // List the whole cart as a single item to account for fees/discounts.
    $data['amount'] = uc_currency_format($order->getTotal(), FALSE, FALSE, '.');

    //$form['#action'] = $this->configuration['ccavenue_server'];
    $form['#action'] = Url::fromRoute('uc_ccavenue.ccavenue_request')->toString();
    foreach ($data as $name => $value) {
      if (!empty($value)) {
        $form[$name] = array('#type' => 'hidden', '#value' => $value);
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
