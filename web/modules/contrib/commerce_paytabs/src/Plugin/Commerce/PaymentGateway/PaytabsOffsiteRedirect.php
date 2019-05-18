<?php

namespace Drupal\commerce_paytabs\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paytabs_offsite_redirect",
 *   label = "Paytabs",
 *   display_label = "Paytabs",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_paytabs\PluginForm\OffsiteRedirect\PaytabsOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "visa", "mastercard",
 *   },
 * )
 */
class PaytabsOffsiteRedirect extends OffsitePaymentGatewayBase implements PaytabsOffsiteRedirectInterface {
  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

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
   * Constructs a new PaytabsOffsiteRedirect object.
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
   * @param \GuzzleHttp\ClientInterface $client
   *   The client.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, LoggerChannelFactoryInterface $logger_channel_factory, ClientInterface $client, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->logger = $logger_channel_factory->get('commerce_paytabs');
    $this->httpClient = $client;
    $this->moduleHandler = $module_handler;
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
      $container->get('http_client'),
      $container->get('module_handler')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_email' => '',
      'secret_key' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant email'),
      '#required' => TRUE,
      '#description' => $this->t('Your merchant email used to sign up with your PayTabs account.'),
      '#attributes' => [
        'placeholder' => \Drupal::config('system.site')->get('mail'),
      ],
      '#default_value' => $this->configuration['merchant_email'],
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Secret Key'),
      '#description' => $this->t('You can find the secret key on your PayTabs Merchant’s Dashboard - PayTabs Services - ecommerce Plugins and API.'),
      '#default_value' => $this->configuration['secret_key'],
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
     $this->configuration['merchant_email'] = $values['merchant_email'];
     $this->configuration['secret_key'] = $values['secret_key'];

      $api_uri = Url::fromUri('https://www.paytabs.com/apiv2/validate_secret_key')->toString();
      $response = $this->doHttpRequest($api_uri, $values);

      if (!$response->response_code == 4000) {
        $form_state->setError($form, $this->t('Invalid API credentials.'));
      }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_email'] = $values['merchant_email'];
      $this->configuration['secret_key'] = $values['secret_key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

    // PayTabs returns payment_reference through a post request
    $paytabs_data = [
      'merchant_email' => $this->configuration['merchant_email'],
      'secret_key' => $this->configuration['secret_key'],
      'payment_reference' => $request->request->get('payment_reference'),
    ];

    $api_uri = Url::fromUri('https://www.paytabs.com/apiv2/verify_payment')->toString();
    try {
      $resource = $this->doHttpRequest($api_uri, $paytabs_data);
      if ($resource->response_code == 100) {
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment         = $payment_storage->create([
          'state'           => 'completed',
          'amount'          => $order->getTotalPrice(),
          'payment_gateway' => $this->entityId,
          'order_id'        => $order->id(),
          'remote_id'       => $resource->pt_invoice_id,
          'remote_state'    => $resource->result,
          'authorized'      => $this->time->getRequestTime(),
        ]);
        $payment->save();
        drupal_set_message($this->t('Your payment was successful with Order ID : @orderid', [
          '@orderid' => $order->id(),
        ]));
      }
      if ($resource->response_code == 481 || $resource->response_code == 482) {
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment = $payment_storage->create([
          'state' => 'authorization',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $this->entityId,
          'order_id' => $order->id(),
          'remote_id' => $resource->pt_invoice_id,
          'remote_state' => $resource->result,
          'authorized' => $this->time->getRequestTime(),
        ]);
        $payment->save();
        $this->logger->alert('If this transaction is genuine, please contact PayTabs customer service to enquire about the feasibility of processing this transaction.');
        }
    } catch (PaymentGatewayException $e) {
      $this->logger->error($e->getMessage());
    }
  }
  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    // @TODO this function is not working - no ping is received from PayTabs
    $status = $request->get('status');
    drupal_set_message($this->t('Payment @status on @gateway but may resume the checkout process here when you are ready.', [
      '@status' => $status,
      '@gateway' => $this->getDisplayLabel(),
    ]), 'error');
  }
  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    // TODO: Implement refundPayment() method.
  }

  /**
   * {@inheritdoc}
   */
  public function doHttpRequest($api_uri, array $paytabs_data) {

    $request = $this->httpClient->post($api_uri,
      [
        'form_params' => $paytabs_data,
      ])->getBody();
    $result = json_decode($request);

    return $result;
  }

  public function getShippingInfo(OrderInterface $order) {

    if (!$this->moduleHandler->moduleExists('commerce_shipping')) {
          return [];
    }
    else {
      // Check if the order references shipments.
      if ($order->hasField('shipments') && !$order->get('shipments')->isEmpty()) {
        $shipping_profiles = [];

        // Loop over the shipments to collect shipping profiles.
        foreach ($order->get('shipments')->referencedEntities() as $shipment) {
          if ($shipment->get('shipping_profile')->isEmpty()) {
            continue;
          }
          $shipping_profile = $shipment->getShippingProfile();
          $shipping_profiles[$shipping_profile->id()] = $shipping_profile;
        }

        if ($shipping_profiles && count($shipping_profiles) === 1) {
          $shipping_profile = reset($shipping_profiles);
          /** @var \Drupal\address\AddressInterface $address */
          $address       = $shipping_profile->address->first();
          $shipping_info = [
            'shipping_first_name'  => $address->getGivenName(),
            'shipping_last_name'   => $address->getFamilyName(),
            'address_shipping'     => $address->getAddressLine1(),
            'city_shipping'        => $address->getLocality(),
            'state_shipping'       => $address->getAdministrativeArea(),
            'postal_code_shipping' => $address->getPostalCode(),
            'country_shipping'     => \Drupal::service('address.country_repository')->get($address->getCountryCode())->getThreeLetterCode(),
          ];
        }
        return $shipping_info;
      }
    }
  }
}
