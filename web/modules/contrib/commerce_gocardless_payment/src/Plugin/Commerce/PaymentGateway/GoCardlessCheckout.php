<?php

namespace Drupal\commerce_gocardless_payment\Plugin\Commerce\PaymentGateway;

use Drupal;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentStorage;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

use GoCardlessPro\Client;
use GoCardlessPro\Environment;

/**
 * Provides the GoCardless Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "gocardless_checkout",
 *   label = @Translation("GoCardless"),
 *   display_label = @Translation("GoCardless"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_gocardless_payment\PluginForm\GoCardlessCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa",
 *   },
 * )
 */
class GoCardlessCheckout extends OffsitePaymentGatewayBase {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The HTTP client.
   *
   * @var \GoCardlessPro\Client
   */
  protected $httpClient;

  /**
   * The price rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * PaymentStorage Interface.
   *
   * @var \Drupal\commerce_payment\PaymentStorage
   */
  protected $paymentStorage;

  /**
   * Constructs a new PaymentGatewayBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   The logger channel factory.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The price rounder.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_payment\PaymentStorage $payment_storage
   *   The payment storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, LoggerChannelFactoryInterface $logger_channel_factory, RounderInterface $rounder, ModuleHandlerInterface $module_handler, EventDispatcherInterface $event_dispatcher, PaymentStorage $payment_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $access_token = $this->configuration['access_token'];
    if ($this->configuration['sandbox']) {
      $environment = Environment::SANDBOX;
    }
    else {
      $environment = Environment::LIVE;
    }
    $client = new Client([
      'access_token' => $access_token,
      'environment' => $environment,
    ]);

    $this->logger = $logger_channel_factory->get('commerce_gocardless_payment');
    $this->httpClient = $client;
    $this->rounder = $rounder;
    $this->moduleHandler = $module_handler;
    $this->eventDispatcher = $event_dispatcher;

    $this->paymentStorage = $payment_storage;
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
      $container->get('logger.factory'),
      $container->get('commerce_price.rounder'),
      $container->get('module_handler'),
      $container->get('event_dispatcher'),

      \Drupal::entityTypeManager()->getStorage('commerce_payment')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'app_id' => '',
      'app_secret' => '',
      'merchant_id' => '',
      'access_token' => '',
      'sandbox' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#description' => $this->t('App identifier'),
      '#default_value' => $this->configuration['app_id'],
      '#required' => FALSE,
    ];
    $form['app_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Secret'),
      '#description' => $this->t('App secret'),
      '#default_value' => $this->configuration['app_secret'],
      '#required' => FALSE,
    ];
    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#description' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => FALSE,
    ];
    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token'),
      '#description' => $this->t('Access token'),
      '#default_value' => $this->configuration['access_token'],
      '#required' => FALSE,
    ];
    $form['webhook_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook secret'),
      '#description' => $this->t('Webhook secret'),
      '#default_value' => $this->configuration['webhook_secret'],
      '#required' => TRUE,
    ];
    $form['sandbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use sandbox.'),
      '#description' => $this->t('Test transactions.'),
      '#default_value' => $this->configuration['sandbox'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!$form_state->getErrors() && $form_state->isSubmitted()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['app_id'] = $values['app_id'];
      $this->configuration['app_secret'] = $values['app_secret'];
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['access_token'] = $values['access_token'];
      $this->configuration['webhook_secret'] = $values['webhook_secret'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['app_id'] = $values['app_id'];
      $this->configuration['app_secret'] = $values['app_secret'];
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['access_token'] = $values['access_token'];
      $this->configuration['webhook_secret'] = $values['webhook_secret'];
      $this->configuration['sandbox'] = $values['sandbox'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

    $session_id = Drupal::service('session_manager')->getId();
    $access_token = $this->configuration['access_token'];
    if ($this->configuration['sandbox']) {
      $environment = Environment::SANDBOX;
    }
    else {
      $environment = Environment::LIVE;
    }
    // Get GoCardless client object.
    $client = new Client([
      'access_token' => $access_token,
      'environment' => $environment,
    ]);

    $redirectFlow = $client->redirectFlows()->complete(
      $request->query->get('redirect_flow_id'),
      ["params" => ["session_token" => $session_id]]
    );
    // Create remote payment.
    $remote_payment = $client->payments()->create([
      "params" => [
        "amount" => (int) $order->getTotalPrice()->getNumber(),
        "currency" => $order->getTotalPrice()->getCurrencyCode(),
        "links" => [
          "mandate" => $redirectFlow->links->mandate,
        ],
        // Almost all resources in the API let you store custom metadata,
        // which you can retrieve later.
        "metadata" => [
          "invoice_number" => $order->id(),
        ],
      ],
      "headers" => [
        "Idempotency-Key" => (string) $order->uuid(),
      ],
    ]);

    // Create orders payment.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'authorization',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_id' => $remote_payment->id,
      'remote_state' => $remote_payment->status,
    ]);

    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {

    $token = $this->configuration['webhook_secret'];

    $raw_payload = file_get_contents('php://input');

    $headers = getallheaders();
    $provided_signature = $headers["Webhook-Signature"];
    $request_content = \GuzzleHttp\json_decode($request->getContent());
    $remote_event = $request_content->events;
    $resource_type = $remote_event[0]->resource_type;
    $action = $remote_event[0]->action;
    $remote_payment_id = $remote_event[0]->links->payment;

    $calculated_signature = hash_hmac("sha256", $raw_payload, $token);

    if ($provided_signature == $calculated_signature) {
      // Get payment object.
      $payment = $this->loadPaymentByRemoteId($remote_payment_id);

      if (isset($payment)) {
        if ($resource_type == 'payments') {
          // Process payment status received.
          // @todo payment updates if needed.
          switch ($action) {
            case 'paid_out':
              $payment->setRemoteState('paid_out');
              $payment->setState('completed');
              break;

            case 'pending_submission':
              $payment->setRemoteState('pending_submission');
              $payment->setState('authorization');
              break;

            case 'charged_back':
              $payment->setRemoteState('charged_back');
              $payment->setState('refunded');
              break;
          }
        }

        $payment->save();
      }

      header("HTTP/1.1 200 OK");
    }
    else {
      header("HTTP/1.1 498 Invalid Token");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(PaymentInterface $payment) {

    $configuration = $this->getConfiguration();
    if ($configuration['sandbox']) {
      $redirectUrl = $this->getRedirectFlow($payment);
      return $redirectUrl;
    }
    else {
      return 'https://pay.gocardless.com/flow/';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getReturnUrl($order_id) {
    return Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $order_id,
      'step' => 'complete',
    ], ['absolute' => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectFlow($payment) {

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    $order_total = number_format($order->getTotalPrice()->getNumber(), 2, '.', ',');
    $order_currency_code = $order->getTotalPrice()->getCurrencyCode();

    $billing_profile = $order->getBillingProfile()->get('address')->getValue();
    $billing_profile_city = $billing_profile[0]['locality'];
    $billing_address_line1 = $billing_profile[0]['address_line1'];
    $billing_given_name = $billing_profile[0]['given_name'];
    $billing_family_name = $billing_profile[0]['family_name'];
    $billing_postal_code = $billing_profile[0]['postal_code'];
    // Send the order's email if not empty.
    $order_email = '';
    if (!empty($order->getEmail())) {
      $order_email = $order->getEmail();
    }

    $session_id = Drupal::service('session_manager')->getId();

    $access_token = $this->configuration['access_token'];
    if ($this->configuration['sandbox']) {
      $environment = Environment::SANDBOX;
    }
    else {
      $environment = Environment::LIVE;
    }

    $client = new Client([
      'access_token' => $access_token,
      'environment' => $environment,
    ]);

    $redirectFlow = $client->redirectFlows()->create([
      "params" => [
        // This will be shown on the payment pages.
        "description" => $this->t("Total") . ' ' . $order_total . $order_currency_code,
        // Not the access token.
        "session_token" => $session_id,
        "success_redirect_url" => $this->getReturnUrl($order->id())->toString(),
        // Optionally, prefill customer details on the payment page.
        "prefilled_customer" => [
          "given_name" => $billing_given_name,
          "family_name" => $billing_family_name,
          "email" => $order_email,
          "address_line1" => $billing_address_line1,
          "city" => $billing_profile_city,
          "postal_code" => $billing_postal_code,
        ],
      ],
    ]);

    return $redirectFlow->redirect_url;
  }

  /**
   * Loads the payment for a given remote id.
   *
   * @param string $remote_id
   *   The remote id property for a payment.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   Payment object.
   *
   * @todo: to be replaced by Commerce core payment storage method
   * @see https://www.drupal.org/node/2856209
   */
  protected function loadPaymentByRemoteId($remote_id) {
    /** @var \Drupal\commerce_payment\PaymentStorage $storage */
    $storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment_by_remote_id = $storage->loadByProperties(['remote_id' => $remote_id]);
    return reset($payment_by_remote_id);
  }

}
