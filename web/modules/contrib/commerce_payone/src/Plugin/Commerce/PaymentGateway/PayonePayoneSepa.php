<?php

namespace Drupal\commerce_payone\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_payone\ErrorHelper;
use Drupal\commerce_payone\Event\CommercePayoneEvents;
use Drupal\commerce_payone\Event\PayoneRequestEvent;
use Drupal\commerce_payone\PayoneApiServiceInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the On-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payone_sepa",
 *   label = "Payone SEPA (On-site)",
 *   display_label = "SEPA",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_payone\PluginForm\Sepa\SepaPaymentMethodAddForm",
 *   },
 *   payment_method_types = {"commerce_payone_sepa"},
 * )
 */
class PayonePayoneSepa extends OnsitePaymentGatewayBase implements PayoneSepaInterface {

  /**
   * The gateway used for making API calls.
   *
   * @var \Drupal\commerce_payone\PayoneClientApi
   */
  protected $api;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    PaymentTypeManager $payment_type_manager,
    PaymentMethodTypeManager $payment_method_type_manager,
    TimeInterface $time,
    PayoneApiServiceInterface $apiService,
    EventDispatcherInterface $event_dispatcher
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->eventDispatcher = $event_dispatcher;
    // You can create an instance of the SDK here and assign it to $this->api.
    // Or inject Guzzle when there's no suitable SDK.
    $this->api = $apiService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('commerce_payone.payment_api'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'merchant_id' => '',
        'portal_id' => '',
        'sub_account_id' => '',
        'key' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];

    $form['portal_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Portal ID'),
      '#default_value' => $this->configuration['portal_id'],
      '#required' => TRUE,
    ];

    $form['sub_account_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sub-Account ID'),
      '#default_value' => $this->configuration['sub_account_id'],
      '#required' => TRUE,
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#default_value' => $this->configuration['key'],
      '#required' => TRUE,
    ];

    $modes = $this->getSupportedModes();
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#options' => $modes,
      '#default_value' => $this->configuration['mode'],
      '#required' => TRUE,
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
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['portal_id'] = $values['portal_id'];
      $this->configuration['sub_account_id'] = $values['sub_account_id'];
      $this->configuration['key'] = $values['key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);

    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billing_address */
    if ($billing_address = $payment_method->getBillingProfile()) {
      $billing_address = $payment_method->getBillingProfile()->get('address')->first();
    } else {
      throw new HardDeclineException('No billing address defined for the payment');
    }

    // Preauthorize payment.
    try {
      $response = $this->requestPreauthorization($payment, $billing_address);
      ErrorHelper::handleErrors($response);
    }
    catch (Exception $e) {
      ErrorHelper::handleException($e);
    }

    // Update the local payment entity.
    $payment->setState('preauthorization');
    $payment->setRemoteId($response->txid);
    $payment->save();

    $owner = $payment_method->getOwner();
    if ($owner && $owner->isAuthenticated()) {
      $this->setRemoteCustomerId($owner, $response->userid);
      $owner->save();
    }

    // Perform capture
    $response = $this->requestCapture($payment, $response->txid);

    $payment_state = $capture ? 'completed' : 'authorization';
    $payment->setState($payment_state);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['preauthorization', 'authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    // Perform the capture request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $remote_id = $payment->getRemoteId();

    try {
      $response = $this->requestCapture($payment, $remote_id);
      ErrorHelper::handleErrors($response);
    }
    catch (Exception $e) {
      ErrorHelper::handleException($e);
    }

    // Update the local payment entity.
    $payment->setState('completed');
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);

    // Perform the void request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    // Validate the requested amount.
    $this->assertRefundAmount($payment, $amount);

    // Perform the refund request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $payment_id = $payment->getRemoteId();

    try {
      $response = $this->requestRefund($payment, $amount, $payment_id);
      // ErrorHelper::handleErrors($response);
    }
    catch (Exception $e) {
      // ErrorHelper::handleException($e);
    }

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    // If the remote API needs a remote customer to be created.
    $owner = $payment_method->getOwner();
    if ($owner && $owner->isAuthenticated()) {
      $customer_id = $this->getRemoteCustomerId($owner);
      // If $customer_id is empty, create the customer remotely and then do
      // $this->setRemoteCustomerId($owner, $customer_id);
      // $owner->save();
    }

    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // Delete the remote record here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    // Delete the local entity.
    $payment_method->delete();
  }

  /**
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   * @param \Drupal\address\Plugin\Field\FieldType\AddressItem $address
   * @return mixed|string
   */
  protected function requestPreauthorization(PaymentInterface $payment, $address) {
    $payment_method = $payment->getPaymentMethod();

    $owner = $payment_method->getOwner();
    if ($owner && $owner->isAuthenticated()) {
      $customer_id = $this->getRemoteCustomerId($owner);
      $customer_email = $owner->getEmail();
    }

    $request = $this->api->getServerApiStandardParameters($this->configuration, 'preauthorization');
    $request['aid'] = $this->configuration['sub_account_id'];
    $request['clearingtype'] = 'elv';
    // Reference must be unique.
    $request['reference'] = $payment->getOrderId() . '_' . $this->time->getCurrentTime();
    $request['amount'] = round($payment->getAmount()->getNumber(), 2) * 100;
    $request['currency'] = $payment->getAmount()->getCurrencyCode();
    if ($customer_id) {
      $request['userid'] = $customer_id;
    }
    // $request['successurl'] = $this->buildSecurityCheckReturnUrl($payment->getOrderId());
    // $request['errorurl'] = $this->buildSecurityCheckCancelUrl($payment->getOrderId());
    // $request['hash'] = $this->api->generateHash($request, $this->configuration['key']);

    $request['firstname'] = $address->getGivenName();
    $request['lastname'] = $address->getFamilyName();
    $request['company'] = $address->getOrganization();
    $request['street'] = $address->getAddressLine1();
    $request['addressaddition'] = $address->getAddressLine2();
    $request['zip'] = $address->getPostalCode();
    $request['city'] = $address->getLocality();
    $request['country'] = $address->getCountryCode();
    $request['iban'] = $payment_method->iban->getString();

    if ($customer_email) {
      $request['email'] = $customer_email;
    }

    // Allow modules to adjust the request.
    $event = new PayoneRequestEvent($payment, $request);
    $this->eventDispatcher->dispatch(CommercePayoneEvents::PayonePreAuthRequestEvent, $event);
    $request = $event->getRequest();

    return $this->api->processHttpPost($request, false);
  }

  /**
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   * @param $payment_id
   * @return mixed
   */
  public function requestCapture(PaymentInterface $payment, $payment_id) {
    $request = $this->api->getServerApiStandardParameters($this->configuration, 'capture');
    $request['amount'] = round($payment->getAmount()->getNumber(), 2) * 100;
    $request['currency'] = $payment->getAmount()->getCurrencyCode();
    $request['txid'] = $payment_id;

    // Allow modules to adjust the request.
    $event = new PayoneRequestEvent($payment, $request);
    $this->eventDispatcher->dispatch(CommercePayoneEvents::PayoneCaptureRequestEvent, $event);
    $request = $event->getRequest();

    return $this->api->processHttpPost($request, FALSE);
  }

  /**
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   * @param \Drupal\commerce_price\Price $amount
   * @param $payment_id
   * @return mixed
   */
  public function requestRefund(PaymentInterface $payment, Price $amount, $payment_id) {
    $request = $this->api->getServerApiStandardParameters($this->configuration, 'refund');
    $request['txid'] = $payment_id;
    $request['sequencenumber'] = 2;
    $request['amount'] = round($amount->getNumber(), 2) * -100;
    $request['currency'] = $amount->getCurrencyCode();

    // Allow modules to adjust the request.
    $event = new PayoneRequestEvent($payment, $request);
    $this->eventDispatcher->dispatch(CommercePayoneEvents::PayoneRefundRequestEvent, $event);
    $request = $event->getRequest();

    return $this->api->processHttpPost($request, FALSE);
  }

}
