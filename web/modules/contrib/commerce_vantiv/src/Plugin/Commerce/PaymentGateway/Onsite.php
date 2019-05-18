<?php

namespace Drupal\commerce_vantiv\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\SoftDeclineException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\commerce_vantiv\Event\FilterVantivRequestEvent;
use Drupal\commerce_vantiv\Event\VantivEvents;
use Drupal\commerce_vantiv\VantivApiHelper as Helper;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use litle\sdk\LitleOnlineRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the Onsite payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "vantiv_onsite",
 *   label = "Vantiv (Onsite)",
 *   display_label = "Vantiv (Onsite)",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_vantiv\PluginForm\Onsite\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa",
 *   },
 *   js_library = "commerce_vantiv/eprotect",
 *   modes = {"pre-live", "live", "post-live"}
 * )
 */
class OnSite extends OnsitePaymentGatewayBase implements OnsiteInterface {

  /**
   * The point of access to the Vantiv API.
   *
   * @var litle\sdk\LitleOnlineRequest
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->api = new LitleOnlineRequest();
    $this->eventDispatcher = $event_dispatcher;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'user' => '',
      'password' => '',
      'currency_merchant_map' => [
        'default' => '',
      ],
      'proxy' => '',
      'paypage_id' => '',
      'batch_requests_path' => '',
      'litle_requests_path' => '',
      'sftp_username' => '',
      'sftp_password' => '',
      'batch_url' => '',
      'tcp_port' => '',
      'tcp_timeout' => '',
      'tcp_ssl' => '1',
      'print_xml' => '0',
      'timeout' => '500',
      'report_group' => 'Default Report Group',
      'mode' => 'test',
      'machine_name' => '',
      'version' => '1',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User name'),
      '#default_value' => $this->configuration['user'],
      '#required' => TRUE,
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->configuration['password'],
      '#required' => TRUE,
    ];
    $form['currency_merchant_map'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Currency -> Merchant ID mapping'),
    ];
    $form['currency_merchant_map']['default'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default'),
      '#default_value' => $this->configuration['currency_merchant_map']['default'],
      '#required' => TRUE,
    ];
    $form['proxy'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proxy'),
      '#default_value' => $this->configuration['proxy'],
    ];
    $form['paypage_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PayPage ID'),
      '#default_value' => $this->configuration['paypage_id'],
    ];
    $form['batch_requests_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Batch Requests Path'),
      '#default_value' => $this->configuration['batch_requests_path'],
    ];
    $form['litle_requests_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Litle Requests Path'),
      '#default_value' => $this->configuration['litle_requests_path'],
    ];
    $form['sftp_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('sFTP Username'),
      '#default_value' => $this->configuration['sftp_username'],
    ];
    $form['sftp_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('sFTP Password'),
      '#default_value' => $this->configuration['sftp_password'],
    ];
    $form['batch_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Batch URL'),
      '#default_value' => $this->configuration['batch_url'],
    ];
    $form['tcp_port'] = [
      '#type' => 'number',
      '#title' => $this->t('TCP Port'),
      '#default_value' => $this->configuration['tcp_port'],
      '#required' => TRUE,
    ];
    $form['tcp_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('TCP Timeout'),
      '#default_value' => $this->configuration['tcp_timeout'],
      '#required' => TRUE,
    ];
    $form['tcp_ssl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('TCP SSL?'),
      '#default_value' => $this->configuration['tcp_ssl'],
    ];
    $form['print_xml'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Print XML?'),
      '#default_value' => $this->configuration['print_xml'],
    ];
    $form['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout'),
      '#default_value' => $this->configuration['timeout'],
      '#required' => TRUE,
    ];
    $form['report_group'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Report Group'),
      '#default_value' => $this->configuration['report_group'],
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
      $this->configuration['currency_merchant_map']['default'] = $values['currency_merchant_map']['default'];
      $keys = [
        'user', 'password', 'proxy', 'paypage_id', 'batch_requests_path',
        'litle_requests_path', 'sftp_username', 'sftp_password',
        'batch_url', 'tcp_port', 'tcp_timeout', 'tcp_ssl', 'print_xml',
        'timeout', 'report_group',
      ];
      foreach ($keys as $key) {
        $this->configuration[$key] = $values[$key];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentOperations(PaymentInterface $payment) {
    $state = $payment->getState()->value;
    $txn_time = $payment->getCompletedTime() ? $payment->getCompletedTime() : $payment->getAuthorizedTime();
    $txn_remote_complete = time() > 60 + $txn_time;
    $txn_same_day = (strtotime('today') < $txn_time && $txn_time < (strtotime('tomorrow') - 1));
    $operations = [];
    if ($txn_remote_complete) {
      $operations['capture'] = [
        'title' => $this->t('Capture'),
        'page_title' => $this->t('Capture payment'),
        'plugin_form' => 'capture-payment',
        'access' => ($state == 'authorization' && !$payment->isExpired()),
      ];
      $operations['void'] = [
        'title' => $this->t('Void'),
        'page_title' => $this->t('Void payment'),
        'plugin_form' => 'void-payment',
        'access' => ($state == 'authorization' && !$payment->isExpired()) || (in_array($state, ['completed', 'refunded']) && $txn_same_day),
      ];
      $operations['refund'] = [
        'title' => $this->t('Refund'),
        'page_title' => $this->t('Refund payment'),
        'plugin_form' => 'refund-payment',
        'access' => in_array($state, ['completed', 'partially_refunded']),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);

    /** @var \Drupal\commerce_price\Price $amount */
    $amount = $payment->getAmount();
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $payment_method->getBillingProfile();
    /** @var \Drupal\user\Entity\User $user */
    $user = $profile->getOwner();
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billing_info */
    $billing_info = $profile->get('address')->first();

    $hash_in = Helper::getApiRequestParamsFromConfig($this->configuration);
    $request_data = [
      'orderId' => $payment->getOrderId(),
      'amount'  => Helper::getVantivAmountFormat($amount->getNumber()),
      'orderSource' => 'ecommerce',
      'billToAddress' => [
        'name' => $billing_info->getGivenName() . ' ' . $billing_info->getFamilyName(),
        'addressLine1' => $billing_info->getAddressLine1(),
        'city' => $billing_info->getLocality(),
        'state' => substr($billing_info->getAdministrativeArea(), -2),
        'zip' => $billing_info->getPostalCode(),
        'country' => $billing_info->getCountryCode(),
        'email' => $user->getEmail(),
      ],
      'token' => [
        'litleToken' => $payment_method->getRemoteId(),
        'expDate' => Helper::getVantivCreditCardExpDate($payment_method),
      ],
    ];

    $event = new FilterVantivRequestEvent($payment, $hash_in, $request_data);
    $this->eventDispatcher->dispatch(VantivEvents::PAYMENT_CREATE_REQUEST, $event);

    try {
      $request_method = $capture ? 'saleRequest' : 'authorizationRequest';
      $request = NestedArray::mergeDeep($hash_in, $event->getRequest());
      $response = $this->api->{$request_method}($request);
    }
    catch (\Exception $e) {
      throw new InvalidRequestException($e->getMessage());
    }

    $response_property = $capture ? 'saleResponse' : 'authorizationResponse';
    $response_array = Helper::getResponseArray($response, $response_property);

    $this->ensureSuccessTransaction($response_array, 'Payment');
    $next_state = $capture ? 'completed' : 'authorization';

    $payment->setState($next_state);
    $payment->setRemoteId($response_array['litleTxnId']);
    if (!$capture) {
      $payment->setExpiresTime(Helper::getAuthorizationExpiresTime($payment));
    }
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    /** @var \Drupal\commerce_price\Price $capture_amount */
    $capture_amount = $amount ?: $payment->getBalance();
    if ($capture_amount->lessThan($payment->getBalance())) {
      $partial_capture = $payment->createDuplicate();
      $partial_capture->state = 'authorization';
      $partial_capture->partial = TRUE;
      $partial_capture->setAmount($capture_amount);
      $partial_capture->setRemoteId($payment->getRemoteId());
      $this->capturePayment($partial_capture, $capture_amount);
      if ($partial_capture->getCompletedTime()) {
        $payment->setAmount($payment->getAmount()->subtract($partial_capture->getAmount()));
        $payment->save();
      }
      return;
    }

    $hash_in = Helper::getApiRequestParamsFromConfig($this->configuration);
    $request_data = [
      'id' => $payment->getAuthorizedTime(),
      'litleTxnId' => $payment->getRemoteId(),
      'amount' => Helper::getVantivAmountFormat($capture_amount->getNumber()),
    ];
    // Part of Vantiv partial capture issue.
    if ($payment->partial) {
      $request_data['partial'] = 'true';
    }

    $event = new FilterVantivRequestEvent($payment, $hash_in, $request_data);
    $this->eventDispatcher->dispatch(VantivEvents::PAYMENT_CAPTURE_REQUEST, $event);

    try {
      $request = NestedArray::mergeDeep($hash_in, $event->getRequest());
      $response = $this->api->captureRequest($request);
    }
    catch (\Exception $e) {
      throw new InvalidRequestException($e->getMessage());
    }
    $response_array = Helper::getResponseArray($response, 'captureResponse');

    $this->ensureSuccessTransaction($response_array, 'Capture');

    $payment->setRemoteId($response_array['litleTxnId']);
    $payment->setAmount($capture_amount);
    $payment->setState('completed');
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $state = $payment->getState()->value;
    $operation = ($state == 'authorization') ? 'authReversal' : 'void';
    $request_operation = "{$operation}Request";
    $response_operation = "{$operation}Response";

    $hash_in = Helper::getApiRequestParamsFromConfig($this->configuration);
    $request_data = [
      'id' => $payment->getAuthorizedTime(),
      'litleTxnId' => $payment->getRemoteId(),
    ];

    $event = new FilterVantivRequestEvent($payment, $hash_in, $request_data);
    $this->eventDispatcher->dispatch(VantivEvents::PAYMENT_VOID_REQUEST, $event);

    try {
      $request = NestedArray::mergeDeep($hash_in, $event->getRequest());
      $response = $this->api->{$request_operation}($request);
    }
    catch (\Exception $e) {
      throw new InvalidRequestException($e->getMessage());
    }
    $response_array = Helper::getResponseArray($response, $response_operation);

    $this->ensureSuccessTransaction($response_array, $operation);
    $next_state = $state == 'authorization' ? 'authorization_voided' : 'refunded';

    $payment->setRemoteId($response_array['litleTxnId']);
    $payment->setState($next_state);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $hash_in = Helper::getApiRequestParamsFromConfig($this->configuration);
    $request_data = [
      'id' => $payment->getAuthorizedTime(),
      'litleTxnId' => $payment->getRemoteId(),
      'amount' => Helper::getVantivAmountFormat($amount->getNumber()),
    ];

    $event = new FilterVantivRequestEvent($payment, $hash_in, $request_data);
    $this->eventDispatcher->dispatch(VantivEvents::PAYMENT_REFUND_REQUEST, $event);

    try {
      $request = NestedArray::mergeDeep($hash_in, $event->getRequest());
      $response = $this->api->creditRequest($request);
    }
    catch (\Exception $e) {
      throw new InvalidRequestException($e->getMessage());
    }
    $response_array = Helper::getResponseArray($response, 'creditResponse');

    $this->ensureSuccessTransaction($response_array, 'Refund');

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
      'vantivResponseType', 'vantivResponsePaypageRegistrationId', 'expiration',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

    $expires = CreditCard::calculateExpirationTimestamp($payment_details['expiration']['month'], $payment_details['expiration']['year']);
    $payment_method->card_type = Helper::getCommerceCreditCardType($payment_details['vantivResponseType']);
    $payment_method->card_number = $payment_details['vantivResponseLastFour'];
    $payment_method->card_exp_month = $payment_details['expiration']['month'];
    $payment_method->card_exp_year = $payment_details['expiration']['year'];
    $payment_method->setRemoteId($payment_details['vantivResponsePaypageRegistrationId']);
    $payment_method->setExpiresTime($expires);
    if ($payment_method->getOwnerId() == 0) {
      $payment_method->setReusable(FALSE);
    }
    $payment_method->save();

    $this->registerToken($payment_method);
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
   * Registers a token with Vantiv from the AJAX provided registration id.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   *
   * @throws \Exception
   * @throws \Drupal\commerce_payment\Exception\InvalidRequestException
   */
  private function registerToken(PaymentMethodInterface $payment_method) {
    $hash_in = Helper::getApiRequestParamsFromConfig($this->configuration);
    /** @var ProfileInterface $billing_profile */
    $billing_profile = $payment_method->getBillingProfile();
    $request_data = [
      'id' => $payment_method->getOriginalId(),
      'customerId' => $billing_profile->getOwnerId(),
      'paypageRegistrationId' => $payment_method->getRemoteId(),
    ];

    try {
      $response = $this->api->registerTokenRequest($hash_in + $request_data);
    }
    catch (\Exception $e) {
      throw new InvalidRequestException($e->getMessage());
    }
    $response_array = Helper::getResponseArray($response, 'registerTokenResponse');
    $this->ensureSuccessTransaction($response_array, 'Token registration');

    $payment_method->setRemoteId($response_array['litleToken']);
    $payment_method->save();
  }

  /**
   * Ensures a successful transaction.
   *
   * Logs and throws an error if response does not contain success data.
   *
   * @param array $response_array
   *   Vantiv response array.
   * @param string $txn_type
   *   Transaction type.
   *
   * @throws SoftDeclineException
   */
  private function ensureSuccessTransaction(array $response_array, $txn_type = 'Transaction') {
    if (!Helper::isResponseSuccess($response_array['response'])) {
      $message = $this->t('@type failed with code @code (@message) (@id).', [
        '@type' => $txn_type,
        '@code' => isset($response_array['response']) ? $response_array['response'] : '',
        '@message' => isset($response_array['message']) ? $response_array['message'] : '',
        '@id' => isset($response_array['litleTxnId']) ? $response_array['litleTxnId'] : '',
      ]);
      throw new SoftDeclineException($message);
    }
  }

}
