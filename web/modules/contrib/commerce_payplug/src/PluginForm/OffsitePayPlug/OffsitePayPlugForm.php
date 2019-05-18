<?php

namespace Drupal\commerce_payplug\PluginForm\OffsitePayPlug;

use Drupal\address\AddressInterface;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Payplug\Exception\ConfigurationException;

/**
 * Class PaymentOffsiteForm
 * @package Drupal\commerce_payplug\PluginForm\OffsitePayPlug
 */
class OffsitePayPlugForm extends BasePaymentOffsiteForm {
  use StringTranslationTrait;

  /**
   * The PayPlug Service interface.
   *
   * @var \Drupal\commerce_payplug\Services\PayPlugServiceInterface
   */
  protected $payPlugService;

  /**
   * Constructs a new PaymentOffsiteForm object.
   */
  function __construct() {
    $this->payPlugService = \Drupal::service('commerce_payplug.payplug.service');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payplug\Plugin\Commerce\PaymentGateway\OffsiteOffsitePayPlug $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $payment_gateway_configuration = $payment_gateway_plugin->getConfiguration();

    // Find API Key.
    $api_key = $payment_gateway_configuration['mode'] == 'live' ? $payment_gateway_configuration['live_apikey'] : $payment_gateway_configuration['test_apikey'];

    // Set API Key
    try {
      $this->payPlugService->setApiKey($api_key);
    } catch (ConfigurationException $e) {
      \Drupal::logger('commerce_payplug')->critical($this->t('PayPlug Gateway could not be initialised in @mode mode.', ['@mode' => $payment_gateway_plugin->getMode()]) . $this->t($e->getMessage()));
      drupal_set_message($this->t('An error occured while contacting the payment gateway. Please contact the site administrator.'), 'error');
      return [];
    }

    // Retrieve billingProfile infos.
    $billing_profile = $payment->getOrder()->getBillingProfile();
    /** @var AddressInterface $address */
    $address = $billing_profile->get('address')->first();
    $billing_email = $payment->getOrder()->getEmail();

    // Build the Payment object.
    /** @var \Payplug\Resource\Payment $payplug_payment */
    $payplug_payment = NULL;
    try {
      $object = [
        'amount' =>  $payment->getAmount()->getNumber() * 100,
        'currency' => $payment->getAmount()->getCurrencyCode(),
        'customer' => [
          'first_name' => $address->getGivenName() ? $address->getGivenName() : NULL,
          'last_name' => $address->getFamilyName() ? $address->getFamilyName() : NULL,
          'email' => $billing_email,
          'address1' => $address->getAddressLine1() ? $address->getAddressLine1() : NULL,
          'address2' => $address->getAddressLine2() ? $address->getAddressLine2() : NULL,
          'postcode' => $address->getPostalCode() ? $address->getPostalCode() : NULL,
          'city' => $address->getLocality() ? $address->getLocality() : NULL,
          'country' => $address->getCountryCode() ? $address->getCountryCode() : NULL,
        ],
        'hosted_payment' => [
          'return_url' => $form['#return_url']->toString(),
          'cancel_url' => $form['#cancel_url']->toString(),
        ],
        'notification_url' => $payment_gateway_plugin->getNotifyUrl()->toString(),
        'metadata' => [
          'order_id' => $payment->getOrder()->id(),
        ]
      ];
      $payplug_payment = $this->payPlugService->createPayPlugPayment($object);
    } catch (\Exception $e) {
      \Drupal::logger('commerce_payplug')->critical($this->t('PayPlug Gateway could not be initialised in @mode mode.', ['@mode' => $payment_gateway_plugin->getMode()]) . $this->t($e->getMessage()));
      drupal_set_message($this->t('An error occured while contacting the payment gateway. Please contact the site administrator.'), 'error');
      return [];
    }
    // Now move on paying.
    $redirect_url = $payplug_payment->hosted_payment->payment_url;
    $form['commerce_message']['#action'] = $redirect_url;

    return $this->buildRedirectForm($form, $form_state, $redirect_url, [], self::REDIRECT_GET);
  }
}
