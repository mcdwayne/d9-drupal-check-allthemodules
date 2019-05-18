<?php

namespace Drupal\commerce_payone\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payone\Event\CommercePayoneEvents;
use Drupal\commerce_payone\Event\PayoneRequestEvent;
use Drupal\commerce_payone\PayoneApiServiceInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payone_paypal",
 *   label = "Payone PayPal (Off-site)",
 *   display_label = "Payone PayPal",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_payone\PluginForm\Paypal\PaypalOffsiteForm",
 *   },
 *   payment_method_types = {"commerce_payone_paypal"},
 * )
 */
class PayonePaypal extends OffsitePaymentGatewayBase implements PayonePaypalInterface {

  /**
   * The gateway used for making API calls.
   *
   * @var \Drupal\commerce_payone\PayoneClientApi
   */
  protected $api;

  /**
   * The payment storage.
   *
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

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
    $this->paymentStorage = $entity_type_manager->getStorage('commerce_payment');
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
  public function initializePaypalApi(PaymentInterface $payment, array $form) {
    $payment_method = $payment->getPaymentMethod();

    $order = $payment->getOrder();
    if ($address = $order->getBillingProfile()->get('address')) {
      $address = $order->getBillingProfile()->get('address')->first();
    } else {
      throw new HardDeclineException('No billing address defined for the payment');
    }

    $request = $this->api->getServerApiStandardParameters($this->configuration, 'authorization');
    $request['aid'] = $this->configuration['sub_account_id'];
    $request['clearingtype'] = 'wlt';
    $request['wallettype'] = 'PPE';
    // Reference must be unique.
    $request['reference'] = $payment->getOrderId() . '_' . $this->time->getCurrentTime();
    $request['amount'] = round($payment->getAmount()->getNumber(), 2) * 100;
    $request['currency'] = $payment->getAmount()->getCurrencyCode();
    $request['firstname'] = $address->getGivenName();
    $request['lastname'] = $address->getFamilyName();
    $request['company'] = $address->getOrganization();
    $request['street'] = $address->getAddressLine1();
    $request['addressaddition'] = $address->getAddressLine2();
    $request['zip'] = $address->getPostalCode();
    $request['city'] = $address->getLocality();
    $request['country'] = $address->getCountryCode();
    $request['onlinebanktransfertype'] = 'PNT';
    $request['bankcountry'] = 'DE';
    $request['successurl'] = $form['#return_url'];
    $request['backurl'] = $form['#cancel_url'];

    // Allow modules to adjust the request.
    $event = new PayoneRequestEvent($payment, $request);
    $this->eventDispatcher->dispatch(CommercePayoneEvents::PayoneInitializeRequestEvent, $event);
    $request = $event->getRequest();

    return $this->api->processHttpPost($request, false);
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    parent::onCancel($order, $request);

    $paypal_gateway_data = $order->getData('paypal_gateway');
    if (empty($paypal_gateway_data['transaction_id'])) {
      throw new InvalidRequestException('Transaction ID missing for this PayPal transaction.');
    }

    $transaction_id = $paypal_gateway_data['transaction_id'];
    $payment = $this->paymentStorage->loadByRemoteId($transaction_id);
    if (empty($payment)) {
      throw new InvalidRequestException('No transaction found for PayPal transaction ID @transaction_id.', ['@transaction_id' => $transaction_id]);
    }

    $payment->state = 'authorization_voided';
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $paypal_gateway_data = $order->getData('paypal_gateway');
    $transaction_id = $paypal_gateway_data['transaction_id'];

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'completed',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_id' => $transaction_id,
      'remote_state' => 'completed',
    ]);
    $payment->save();
  }

}
