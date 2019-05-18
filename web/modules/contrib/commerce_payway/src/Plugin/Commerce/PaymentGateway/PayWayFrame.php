<?php

namespace Drupal\commerce_payway\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_payway\Client\PayWayRestApiClientInterface;
use Drupal\commerce_payway\Exception\PayWayClientException;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the PayWay Frame payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payway_frame",
 *   label = "PayWay Frame",
 *   display_label = "PayWay Frame",
 *   forms = {
 *     "add-payment-method" =
 *   "Drupal\commerce_payway\PluginForm\PayWayFrame\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"payway"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard",
 *   "visa",
 *   },
 *   js_library = "commerce_payway/frame_form",
 * )
 */
class PayWayFrame extends OnsitePaymentGatewayBase {

  /**
   * The PayWay REST API Client.
   *
   * @var \Drupal\commerce_payway\Client\PayWayRestApiClientInterface
   */
  private $payWayRestApiClient;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The default currency.
   */
  const CURRENCY = 'aud';

  /**
   * The default transaction type.
   */
  const TRANSACTION_TYPE = 'payment';

  /**
   * Constructs a new PayWayFrame object.
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
   * @param \Drupal\commerce_payway\Client\PayWayRestApiClientInterface $payWayRestApiClient
   *   The PayWay REST API Client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   A logger factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    PaymentTypeManager $payment_type_manager,
    PaymentMethodTypeManager $payment_method_type_manager,
    TimeInterface $time,
    PayWayRestApiClientInterface $payWayRestApiClient,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition,
      $entity_type_manager, $payment_type_manager,
      $payment_method_type_manager, $time);

    $this->payWayRestApiClient = $payWayRestApiClient;
    $this->logger = $logger_factory->get('commerce_payway');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('commerce_payway.rest_api.client'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'merchant_id' => '',
        'api_url' => '',
        'secret_key_test' => '',
        'publishable_key_test' => '',
        'secret_key' => '',
        'publishable_key' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => t('Merchant Id'),
      '#description' => t('eg. TEST'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];

    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Url'),
      '#default_value' => $this->configuration['api_url'],
      '#description' => t('eg. https://api.payway.com.au/rest/v1/transactions'),
      '#required' => TRUE,
    ];

    $form['test'] = [
      '#type' => 'fieldset',
      '#title' => t('Tests keys'),
    ];

    $form['test']['secret_key_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test Secret Key'),
      '#default_value' => $this->configuration['secret_key_test'],
      '#required' => TRUE,
    ];

    $form['test']['publishable_key_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test Publishable Key'),
      '#default_value' => $this->configuration['publishable_key_test'],
      '#required' => TRUE,
    ];

    $form['live'] = [
      '#type' => 'fieldset',
      '#title' => t('Live keys'),
    ];

    $form['live']['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Live Secret Key'),
      '#default_value' => $this->configuration['secret_key'],
    ];

    $form['live']['publishable_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Live Publishable Key'),
      '#default_value' => $this->configuration['publishable_key'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values =& $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['api_url'] = $values['api_url'];
      $this->configuration['secret_key_test'] = $values['test']['secret_key_test'];
      $this->configuration['publishable_key_test'] = $values['test']['publishable_key_test'];
      $this->configuration['secret_key'] = $values['live']['secret_key'];
      $this->configuration['publishable_key'] = $values['live']['publishable_key'];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws HardDeclineException
   * @throws \Exception
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    if ($payment->getState()->value !== 'new') {
      throw new \InvalidArgumentException('The provided payment is in an invalid state.');
    }

    /**
     * @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
     */
    $payment_method = $payment->getPaymentMethod();
    if ($payment_method === NULL) {
      throw new \InvalidArgumentException('The provided payment has no payment method referenced.');
    }

    /**
     * @var \Drupal\commerce_order\Entity\OrderInterface $order
     */
    $order = $payment->getOrder();

    // Make a request to PayWay.
    try {
      $this->payWayRestApiClient->doRequest($payment, $this->configuration);
      $result = json_decode($this->payWayRestApiClient->getResponse());
    } catch (PayWayClientException $e) {
      $this->deletePayment($payment, $order);
      $this->logger->warning($e->getMessage());
      throw new HardDeclineException('The payment request failed.', 0, $e);
    }

    // If the payment is not approved.
    if ($result->status !== 'approved'
      && $result->status !== 'approved*'
    ) {
      $this->deletePayment($payment, $order);
      $errorMessage = $result->responseCode . ': ' . $result->responseText;
      $this->logger->error($errorMessage);
      throw new HardDeclineException('The provided payment method has been declined');
    }

    // Update the local payment entity.
    $request_time = $this->time->getRequestTime();
    $payment->state = $capture ? 'completed' : 'authorization';
    $payment->setRemoteId($result->transactionId);
    $payment->setAuthorizedTime($request_time);
    if ($capture) {
      $payment->setCompletedTime($request_time);
    }
    $payment->save();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createPaymentMethod(
    PaymentMethodInterface $payment_method,
    array $payment_details
  ) {
    $required_keys = [
      // The expected keys are payment gateway specific and usually match
      // a PaymentMethodAddForm form elements. They are expected to be valid.
      'payment_credit_card_token',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf(
          '$payment_details must contain the %s key.', $required_key));
      }
    }

    $payment_method->setExpiresTime(0);
    $payment_method->setReusable(FALSE);
    $payment_method->setRemoteId($payment_details['payment_credit_card_token']);
    $payment_method->setDefault(FALSE);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
  }

  /**
   * Delete the payment instance to fix the list of payment methods.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The current instance of payment.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The current order.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \InvalidArgumentException
   */
  public function deletePayment(
    PaymentInterface $payment,
    OrderInterface $order
  ) {
    $payment->delete();
    $order->set('payment_method', NULL);
    $order->set('payment_gateway', NULL);
    $order->save();
  }

  /**
   * Get the publishable Key.
   *
   * @return string
   *    The publishable key.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getPublishableKey() {
    switch ($this->configuration['mode']) {
      case 'test':
        $output = $this->configuration['publishable_key_test'];
        break;

      case 'live':
        $output = $this->configuration['publishable_key'];
        break;

      default:
        throw new MissingDataException('The publishable key is empty.');
    }
    return $output;
  }

  /**
   * Get the secret Key.
   *
   * @return string
   *    The secret key.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getSecretKey() {
    switch ($this->configuration['mode']) {
      case 'test':
        $output = $this->configuration['secret_key_test'];
        break;

      case 'live':
        $output = $this->configuration['secret_key'];
        break;

      default:
        throw new MissingDataException('The private key is empty.');
    }
    return $output;
  }

}
