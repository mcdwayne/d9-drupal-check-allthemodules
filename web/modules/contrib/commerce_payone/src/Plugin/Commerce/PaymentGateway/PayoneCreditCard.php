<?php

namespace Drupal\commerce_payone\Plugin\Commerce\PaymentGateway;

use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\CreditCard;
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
use Drupal\Core\Url;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the On-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payone_cc",
 *   label = "Payone Credit Card (On-site)",
 *   display_label = "Payone Credit Card",
 *    forms = {
 *     "add-payment-method" = "Drupal\commerce_payone\PluginForm\CreditCard\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 *   js_library = "commerce_payone/form",
 * )
 */
class PayoneCreditCard extends OnsitePaymentGatewayBase implements PayoneCreditCardInterface {

  /**
   * The Stripe gateway used for making API calls.
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
      'mode' => 'test',
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
    ];

    $form['portal_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Portal ID'),
      '#default_value' => $this->configuration['portal_id'],
    ];

    $form['sub_account_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sub-Account ID'),
      '#default_value' => $this->configuration['sub_account_id'],
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PAYONE Key'),
      '#default_value' => $this->configuration['key'],
    ];
    $card_type_map = array_flip(self::creditCardMap());
    $credit_card_types = CreditCard::getTypeLabels();
    $form['allowed_cards'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => array_intersect_key($credit_card_types, $card_type_map),
      '#title' => $this->t('Allowed credit card types'),
      '#default_value' => $this->configuration['allowed_cards'],
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
      $this->configuration['mode'] = $this->getMode();
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['portal_id'] = $values['portal_id'];
      $this->configuration['sub_account_id'] = $values['sub_account_id'];
      $this->configuration['key'] = $values['key'];
      $this->configuration['allowed_cards'] = $values['allowed_cards'];
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
    }
    else {
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

    // 3-D Secure check if status 'REDIRECT'.
    if ($response->status == 'REDIRECT') {
      throw new NeedsRedirectException($response->redirecturl);
    }

    // Capture payment.
    if ($capture) {
      try {
        $response = $this->requestCapture($payment, $response->txid);
        ErrorHelper::handleErrors($response);
      }
      catch (Exception $e) {
        ErrorHelper::handleException($e);
      }
    }

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
      ErrorHelper::handleErrors($response);
    }
    catch (Exception $e) {
      ErrorHelper::handleException($e);
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
    $required_keys = [
      // The expected keys are payment gateway specific and usually match
      // the PaymentMethodAddForm form elements. They are expected to be valid.
      'pseudocardpan',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }
    // Perform the create request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    // You might need to do different API requests based on whether the
    // payment method is reusable: $payment_method->isReusable().
    // Non-reusable payment methods usually have an expiration timestamp.
    $payment_method->card_type = $this->mapCreditCardType($payment_details['cardtype']);
    // Only the last 4 numbers are safe to store.
    $payment_method->card_number = substr($payment_details['truncatedcardpan'], -4);
    $payment_method->card_exp_month = substr($payment_details['cardexpiredate'], -2);
    $payment_method->card_exp_year = substr(date('Y'), 0, 2) . substr($payment_details['cardexpiredate'], 0, 2);
    $expires = CreditCard::calculateExpirationTimestamp($payment_method->card_exp_month->value, $payment_method->card_exp_year->value);

    // The remote ID returned by the request.
    $remote_id = $payment_details['pseudocardpan'];

    $payment_method->setRemoteId($remote_id);
    $payment_method->setExpiresTime($expires);
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
   * Maps the Payone credit card type to a Commerce credit card type.
   *
   * @param string $card_type
   *   The Payone credit card type.
   *
   * @return string
   *   The Commerce credit card type.
   */
  protected function mapCreditCardType($card_type) {
    $map = self::creditCardMap();
    if (!isset($map[$card_type])) {
      throw new HardDeclineException(sprintf('Unsupported credit card type "%s".', $card_type));
    }

    return $map[$card_type];
  }

  /**
   * Simple map of credit card keys.
   */
  public static function creditCardMap() {
    $map = [
      'V' => 'visa',
      'M' => 'mastercard',
      'A' => 'amex',
      'D' => 'dinersclub',
      'J' => 'jcb',
      'O' => 'maestro',
      'U' => 'maestro',
      'C' => 'discover',
    ];
    return $map;
  }

  /**
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   * @param \Drupal\address\Plugin\Field\FieldType\AddressItem $address
   * @return mixed|string
   */
  protected function requestPreauthorization(PaymentInterface $payment, AddressItem $address) {
    $payment_method = $payment->getPaymentMethod();
    $remote_id = $payment_method->getRemoteId();

    $owner = $payment_method->getOwner();
    if ($owner && $owner->isAuthenticated()) {
      $customer_id = $this->getRemoteCustomerId($owner);
      $customer_email = $owner->getEmail();
    }

    $request = $this->api->getClientApiStandardParameters($this->configuration, 'preauthorization');
    $request['aid'] = $this->configuration['sub_account_id'];
    $request['clearingtype'] = 'cc';
    // Reference must be unique.
    $request['reference'] = $payment->getOrderId() . '_' . $this->time->getCurrentTime();
    $request['amount'] = round($payment->getAmount()->getNumber(), 2) * 100;
    $request['currency'] = $payment->getAmount()->getCurrencyCode();
    if ($customer_id) {
      $request['userid'] = $customer_id;
    }
    $request['successurl'] = $this->buildSecurityCheckReturnUrl($payment->getOrderId());
    $request['errorurl'] = $this->buildSecurityCheckCancelUrl($payment->getOrderId());
    $request['hash'] = $this->api->generateHash($request, $this->configuration['key']);

    $request['firstname'] = $address->getGivenName();
    $request['lastname'] = $address->getFamilyName();
    $request['company'] = $address->getOrganization();
    $request['street'] = $address->getAddressLine1();
    $request['addressaddition'] = $address->getAddressLine2();
    $request['zip'] = $address->getPostalCode();
    $request['city'] = $address->getLocality();
    $request['country'] = $address->getCountryCode();

    if ($customer_email) {
      $request['email'] = $customer_email;
    }

    $request['pseudocardpan'] = $remote_id;

    // Allow modules to adjust the request.
    $event = new PayoneRequestEvent($payment, $request);
    $this->eventDispatcher->dispatch(CommercePayoneEvents::PayonePreAuthRequestEvent, $event);
    $request = $event->getRequest();

    return $this->api->processHttpPost($request);
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

  /**
   * Processes the "return" request for 3-D Secure check.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function onSecurityCheckReturn(OrderInterface $order, $capture = TRUE) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->getPayment($order);

    if ($capture) {
      $this->capturePayment($payment);
    }

    $payment_state = $capture ? 'completed' : 'authorization';
    $payment->setState($payment_state);
    $payment->save();
  }

  /**
   * Processes the "cancel" request for 3-D Secure check.
   *
   * Allows the payment gateway to clean up any data added to the $order, set
   * a message for the customer.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function onSecurityCheckCancel(OrderInterface $order) {
    $this->messenger()->addMessage($this->t('You have canceled checkout at @gateway but may resume the checkout process here when you are ready.', [
      '@gateway' => $this->getDisplayLabel(),
    ]));
  }

  /**
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @return bool|\Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected function getPayment(OrderInterface $order) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface[] $payments */
    $payments = $this->entityTypeManager
      ->getStorage('commerce_payment')
      ->loadByProperties(['order_id' => $order->id()]);

    if (empty($payments)) {
      return FALSE;
    }
    foreach ($payments as $payment) {
      if ($payment->getPaymentGateway()->getPluginId() !== 'payone_cc' || $payment->getAmount()->compareTo($order->getTotalPrice()) !== 0) {
        continue;
      }
      $payone_payment = $payment;
    }
    return empty($payone_payment) ? FALSE : $payone_payment;
  }

  /**
   * Builds the URL to the "return" page.
   *
   * @param int $order_id
   *   The order id.
   *
   * @return string
   *   The "return" page url.
   */
  protected function buildSecurityCheckReturnUrl($order_id) {
    return Url::fromRoute('commerce_payone.3ds.return', [
      'commerce_order' => $order_id,
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

  /**
   * Builds the URL to the "cancel" page.
   *
   * @param int $order_id
   *   The order id.
   *
   * @return string
   *   The "cancel" page url.
   */
  protected function buildSecurityCheckCancelUrl($order_id) {
    return Url::fromRoute('commerce_payone.3ds.cancel', [
      'commerce_order' => $order_id,
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

}
