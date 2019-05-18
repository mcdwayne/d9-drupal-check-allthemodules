<?php

namespace Drupal\commerce_affirm\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsVoidsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Provides the Affirm Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "affirm_redirect",
 *   label = @Translation("Affirm (Redirect)"),
 *   display_label = @Translation("Affirm"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_affirm\PluginForm\RedirectForm",
 *   },
 * )
 */
class Redirect extends OffsitePaymentGatewayBase implements SupportsRefundsInterface, SupportsVoidsInterface, SupportsAuthorizationsInterface {

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
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

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
   * @param \GuzzleHttp\ClientInterface $client
   *   The client.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The price rounder.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, LoggerChannelFactoryInterface $logger_channel_factory, ClientInterface $client, RounderInterface $rounder, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->logger = $logger_channel_factory->get('commerce_affirm');
    $this->httpClient = $client;
    $this->rounder = $rounder;
    $this->messenger = $messenger;
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
      $container->get('commerce_price.rounder'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $return = [
      'window_mode' => 'redirect',
      'public_key' => '',
      'private_key' => '',
      'financial_key' => '',
      'log' => FALSE,
    ];
    return $return + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['window_mode'] = [
      '#type' => 'radios',
      '#title' => t('Window mode'),
      '#options' => [
        'redirect' => t('Redirect'),
        'modal' => t('Modal'),
      ],
      '#default_value' => $this->configuration['window_mode'],
    ];

    // Public key.
    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => t('Public Key'),
      '#description' => t('Add your public key for the gateway.'),
      '#default_value' => $this->configuration['public_key'],
      '#required' => TRUE,
    ];
    // Private key.
    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => t('Private Key'),
      '#description' => t('Add your private key for the gateway.'),
      '#default_value' => $this->configuration['private_key'],
      '#required' => TRUE,
    ];
    // Financial Product Key.
    $form['financial_key'] = [
      '#type' => 'textfield',
      '#title' => t('Financial Product Key'),
      '#description' => t('Add your financial key for the gateway.'),
      '#default_value' => $this->configuration['financial_key'],
      '#required' => TRUE,
    ];

    $form['log'] = [
      '#type' => 'checkbox',
      '#title' => t('Log API and related data for debugging'),
      '#default_value' => $this->configuration['log'],
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
      $this->configuration['window_mode'] = $values['window_mode'];
      $this->configuration['public_key'] = $values['public_key'];
      $this->configuration['private_key'] = $values['private_key'];
      $this->configuration['financial_key'] = $values['financial_key'];
      $this->configuration['log'] = $values['log'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $data = [
      'checkout_token' => $request->request->get('checkout_token'),
      'order_id' => $order->id(),
    ];
    $response = $this->apiRequest('authorization', $data);

    if (empty($response)) {
      $this->messenger->addError($this->t('We could not complete your payment with Affirm. Please try again or contact us if the problem persists.'));
      throw new PaymentGatewayException($this->t('We could not complete your payment with Affirm. Please try again or contact us if the problem persists.'));
    }

    if ($response->getStatusCode() == 200) {
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      $response_json = json_decode($response->getBody());
      // Checks if the response contains an error.
      if (isset($response_json->status_code)) {
        // Display an error message and remain on the same page.
        $this->messenger->addError($this->t('We could not complete your payment with Affirm. Please try again or contact us if the problem persists.'));
        throw new PaymentGatewayException($this->t('We could not complete your payment with Affirm. Please try again or contact us if the problem persists.'));
      }

      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = $payment_storage->create([
        'state' => 'authorization',
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $this->entityId,
        'order_id' => $order->id(),
        'remote_id' => $response_json->id,
        'remote_status' => 'authorize',
      ]);
      $payment->save();
      /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
      $checkout_flow = $order->get('checkout_flow')->entity;
      $capture = $checkout_flow->getPlugin()->getConfiguration()['panes']['payment_process']['capture'];

      if ($response_json->metadata['capture'] || $capture) {
        $this->capturePayment($payment);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    if (!empty($amount) && $amount->lessThan($payment->getAmount())) {
      $vars = [
        '@id' => $payment->id(),
      ];
      $this->logger->warning($this->t('Partial capture attempted for payment @id.', $vars));
      throw new PaymentGatewayException($this->t('Affirm does not support partial captures.'));
    }
    $amount = $payment->getAmount();

    $data = [
      'amount' => $this->toMinorUnits($amount),
    ];
    try {
      $response = $this->apiRequest('capture', $data, $payment);
    }
    catch (\Exception $e) {
      $this->logger->warning($e->getMessage());
      throw new PaymentGatewayException($this->t('We could not complete your payment with Affirm. Please try again or contact us if the problem persists.'));
    }
    $response_json = json_decode($response->getBody());

    if (empty($response_json) || isset($response_json->status_code)) {
      throw new PaymentGatewayException($this->t('We could not complete your payment with Affirm. Please try again or contact us if the problem persists.'));
    }
    $payment->setState('completed');
    $payment->setRemoteState($response_json->type);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    try {
      $response = $this->apiRequest('void', [], $payment);
    }
    catch (\Exception $e) {
      $this->logger->warning($e->getMessage());
      throw new PaymentGatewayException($this->t('We could not void your payment with Affirm. Please try again or contact us if the problem persists.'));
    }
    $response_json = json_decode($response->getBody());

    if (empty($response_json) || isset($response_json->status_code)) {
      throw new PaymentGatewayException($this->t('We could not void your payment with Affirm. Please try again or contact us if the problem persists.'));
    }
    $payment->setState('authorization_voided');
    $payment->setRemoteState($response_json->type);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    if (!$amount) {
      $amount = $payment->getAmount();
    }
    $data = [
      'amount' => $this->toMinorUnits($amount),
    ];
    try {
      $response = $this->apiRequest('refund', $data, $payment);
    }
    catch (\Exception $e) {
      $this->logger->warning($e->getMessage());
      throw new PaymentGatewayException($this->t('We could not refund your payment with Affirm. Please try again or contact us if the problem persists.'));
    }
    $response_json = json_decode($response->getBody());

    if (empty($response_json) || isset($response_json->status_code)) {
      throw new PaymentGatewayException($this->t('We could not refund your payment with Affirm. Please try again or contact us if the problem persists.'));
    }
    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }
    $payment->setRemoteState($response_json->type);
    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * Returns the base URL to Affirm API server.
   *
   * @return string|bool
   *   The Base URL to use to submit requests to the Affirm API server.
   */
  public function serverUrl() {
    switch ($this->getMode()) {
      case 'live':
        return 'https://api.affirm.com/api/v2/charges/';

      case 'test':
        return 'https://sandbox.affirm.com/api/v2/charges/';
    }
    return FALSE;
  }

  /**
   * Returns the URL to the Affirm server determined by the transaction type.
   *
   * @param string $request_type
   *   The transaction request type (authorization/capture/refund/void).
   * @param \Drupal\commerce_payment\Entity\\PaymentInterface $payment
   *   The payment entity to be captured. The object is required if
   *   you want to do something else than an authorization.
   *
   * @return string|bool
   *   The URL to use to submit requests to the Affirm server.
   */
  public function apiServerUrl($request_type, PaymentInterface $payment = NULL) {
    $api_url = $this->serverUrl();

    switch ($request_type) {
      case 'authorization':
        return $api_url;

      case 'capture':
        if (empty($payment)) {
          $this->messenger->addStatus($this->t('You can not create a capture request without specifying a payment.'));
          return FALSE;
        }
        return $api_url . $payment->getRemoteId() . '/capture';

      case 'refund':
        if (empty($payment)) {
          $this->messenger->addStatus($this->t('You can not create a credit request without specifying a payment.'));
          return FALSE;
        }
        return $api_url . $payment->getRemoteId() . '/refund';

      case 'void':
        if (empty($payment)) {
          $this->messenger->addStatus($this->t('You can not create a void request without specifying a payment.0'));
          return FALSE;
        }
        return $api_url . $payment->getRemoteId() . '/void';
    }
    return FALSE;
  }

  /**
   * Perform a request against the Affirm API.
   *
   * @param string $request_type
   *   The transaction request type (authorization/capture/refund/void).
   * @param array $data
   *   Data to send to Affirm.
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The Affirm response.
   */
  public function apiRequest($request_type, array $data, PaymentInterface $payment = NULL) {
    $options = [
      'auth' => [
        $this->configuration['public_key'],
        $this->configuration['private_key'],
      ],
      'json' => $data,
    ];

    return $this->httpClient->post($this->apiServerUrl($request_type, $payment), $options);
  }

}
