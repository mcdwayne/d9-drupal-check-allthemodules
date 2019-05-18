<?php

namespace Drupal\commerce_cashpresso\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_cashpresso\PartnerInfo;
use Drupal\commerce_cashpresso\PartnerInfoStoreInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\HasPaymentInstructionsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\profile\Entity\ProfileInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the cashpresso payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "cashpresso",
 *   label = "cashpresso",
 *   display_label = "Financing with cashpresso",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_cashpresso\PluginForm\CashpressoForm",
 *   },
 * )
 */
class CashpressoGateway extends OffsitePaymentGatewayBase implements CashpressoGatewayInterface, HasPaymentInstructionsInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The payment storage.
   *
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

  /**
   * The partner info store.
   *
   * @var \Drupal\commerce_cashpresso\PartnerInfoStoreInterface
   */
  protected $partnerInfoStore;

  /**
   * Constructs a new CashpressoGateway object.
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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \GuzzleHttp\Client $http_client
   *   The http client.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\commerce_cashpresso\PartnerInfoStoreInterface $partner_info_store
   *   The partner info store.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, DateFormatterInterface $date_formatter, Client $http_client, LanguageManagerInterface $language_manager, LoggerChannelFactoryInterface $logger_channel_factory, MessengerInterface $messenger, PartnerInfoStoreInterface $partner_info_store) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->dateFormatter = $date_formatter;
    $this->httpClient = $http_client;
    $this->languageManager = $language_manager;
    $this->logger = $logger_channel_factory->get('commerce_cashpresso');
    $this->messenger = $messenger;
    $this->paymentStorage = $entity_type_manager->getStorage('commerce_payment');
    $this->partnerInfoStore = $partner_info_store;
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
      $container->get('date.formatter'),
      $container->get('http_client'),
      $container->get('language_manager'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('commerce_cashpresso.partner_info_store')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => '',
      'secret' => '',
      'order_valid_time' => 168,
      'interest_free_days_merchant' => 0,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#default_value' => $this->configuration['secret'],
      '#required' => TRUE,
    ];

    $form['order_valid_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Order valid time'),
      '#description' => $this->t('The number of hours, how long an authorized but not completed payment, stays valid.'),
      '#default_value' => $this->configuration['order_valid_time'],
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
    ];

    $form['interest_free_days_merchant'] = [
      '#type' => 'number',
      '#title' => $this->t('Interest free days'),
      '#description' => $this->t('The number of interest free days you grant in addition to the default cashpresso interest free period.'),
      '#default_value' => $this->configuration['interest_free_days_merchant'],
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 1,
    ];

    if (!empty($this->configuration['api_key']) && !empty($this->configuration['secret'])) {
      $partner_info = $this->fetchPartnerInfo(FALSE);
      if ($partner_info) {
        $form['partner_info'] = [
          '#type' => 'details',
          '#title' => $this->t('Partner info'),
        ];
        $form['partner_info']['status'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Status'),
          '#description' => $this->t('The status of your partner account. One of PENDING, ACTIVE or DECLINED. Please note that you can only send in payment requests if your account is in state ACTIVE.'),
          '#disabled' => TRUE,
          '#default_value' => $partner_info->getStatus(),
        ];
        if ($partner_info->getBrandName()) {
          $form['partner_info']['brand_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Brand name'),
            '#disabled' => TRUE,
            '#default_value' => $partner_info->getBrandName(),
          ];
        }
        $form['partner_info']['company_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Company name'),
          '#disabled' => TRUE,
          '#default_value' => $partner_info->getCompanyName(),
        ];
        $form['partner_info']['company_url'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Company url'),
          '#disabled' => TRUE,
          '#default_value' => $partner_info->getCompanyUrl(),
        ];
        $form['partner_info']['email'] = [
          '#type' => 'textfield',
          '#title' => $this->t('E-mail address'),
          '#disabled' => TRUE,
          '#default_value' => $partner_info->getEmail(),
        ];
        $form['partner_info']['holder'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Holder'),
          '#disabled' => TRUE,
          '#default_value' => $partner_info->getHolder(),
        ];
        $form['partner_info']['iban'] = [
          '#type' => 'textfield',
          '#title' => $this->t('IBAN'),
          '#disabled' => TRUE,
          '#default_value' => $partner_info->getIban(),
        ];
        $form['partner_info']['interest_free_enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Interest free periods'),
          '#description' => $this->t('Whether you are allowed to set interest free periods on a per-purchase basis. These interest free days are in addition to interest free periods offered by cashpresso directly and may affect pricing. If you are interested in offering interest free periods to your customer please ask your account manager for further information.'),
          '#disabled' => TRUE,
          '#default_value' => $partner_info->isInterestFreeEnabled(),
        ];
        $form['partner_info']['interest_free_max_duration'] = [
          '#type' => 'number',
          '#title' => $this->t('Interest free max duration'),
          '#description' => $this->t('The maximum number of interest free days you are allowed to offer your customers.'),
          '#disabled' => TRUE,
          '#default_value' => $partner_info->getInterestFreeMaxDuration(),
        ];
        $form['partner_info']['currency'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Currency'),
          '#disabled' => TRUE,
          '#default_value' => $partner_info->getCurrencyCode(),
        ];
        $form['partner_info']['min_payback_amount'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Minimum payback amount'),
          '#description' => $this->t('The minimum amount a customer has to pay per month in the respective currency value. cashpresso payback terms usually come with a paybackRate in percent and a minimum amount.'),
          '#disabled' => TRUE,
          '#default_value' => $partner_info->getMinPaybackAmount(),
        ];
        $form['partner_info']['payback_rate'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Payback rate'),
          '#description' => $this->t('The payback rate in percent.'),
          '#disabled' => TRUE,
          '#default_value' => $partner_info->getPaybackRate(),
        ];
        $form['partner_info']['financing_limit'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Financing limit'),
          '#description' => $this->t('The highest amount new customers can finance with cashpresso.'),
          '#disabled' => TRUE,
          '#default_value' => sprintf('%s %s', $partner_info->getFinancingLimit()->getCurrencyCode(), $partner_info->getFinancingLimit()->getNumber()),
        ];
        $form['partner_info']['prepayment_limit'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Prepayment limit'),
          '#description' => $this->t('The highest amount for prepayment.'),
          '#disabled' => TRUE,
          '#default_value' => sprintf('%s %s', $partner_info->getPrepaymentLimit()->getCurrencyCode(), $partner_info->getPrepaymentLimit()->getNumber()),
        ];
        $form['partner_info']['total_limit'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Total limit'),
          '#description' => $this->t('The maximum amount a customer can pay with the cashpresso payment method.'),
          '#disabled' => TRUE,
          '#default_value' => sprintf('%s %s', $partner_info->getTotalLimit()->getCurrencyCode(), $partner_info->getTotalLimit()->getNumber()),
        ];
        $form['partner_info']['nominal_interest_rate_range'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Nominal interest rate'),
          '#description' => $this->t('The nominal interest rate range in percent.'),
          '#disabled' => TRUE,
          '#default_value' => sprintf('%s%% - %s%%', $partner_info->getMinNominalInterestRate(), $partner_info->getMaxNominalInterestRate()),
        ];
        $form['partner_info']['effective_interest_rate_range'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Effective interest rate'),
          '#description' => $this->t('The effective interest rate range in percent.'),
          '#disabled' => TRUE,
          '#default_value' => sprintf('%s%% - %s%%', $partner_info->getMinEffectiveInterestRate(), $partner_info->getMaxEffectiveInterestRate()),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if (!empty($values['interest_free_days_merchant'])) {
      $partner_info = $this->fetchPartnerInfo(TRUE);
      if (empty($partner_info)) {
        $form_state->setError($form['interest_free_days_merchant'], $this->t('An error occurred while trying to fetch your partner info. The merchant free days cannot be validated. Please check the error logs and/or your submitted API key and secret!'));
      }
      else {
        $max_interest_free = $partner_info->isInterestFreeEnabled() ? $partner_info->getInterestFreeMaxDuration() : 0;
        if ($form['order_valid_time'] > $max_interest_free) {
          $form_state->setError($form['interest_free_days_merchant'], $this->t('Interest free days must not exceed @limit!', ['@limit' => $max_interest_free]));
        }
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

      $this->configuration['api_key'] = $values['api_key'];
      $this->configuration['secret'] = $values['secret'];
      $this->configuration['order_valid_time'] = $values['order_valid_time'];
      $this->configuration['interest_free_days_merchant'] = $values['interest_free_days_merchant'];

      $partner_info = $this->fetchPartnerInfo(TRUE);
      if (empty($partner_info)) {
        $this->messenger->addWarning($this->t('An error occurred while trying to fetch your partner info. The merchant free days cannot be validated. Please check the error logs and/or your submitted API key and secret!'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    parent::onNotify($request);

    /*
     * A string array containing the following keys:
     *   - referenceId
     *   - usage
     *   - status (one of SUCCESS, CANCELLED, TIMEOUT)
     *   - verificationHash
     */
    $request_content = Json::decode($request->getContent());
    $reference_id = $request_content['referenceId'];

    $payment = $this->paymentStorage->loadByRemoteId($reference_id);
    if (empty($payment)) {
      throw new InvalidRequestException($this->t('No transaction found for cashpresso reference ID @reference_id.', ['@reference_id' => $reference_id]));
    }
    if (empty($request_content['status'])) {
      throw new InvalidRequestException($this->t('No payment status set in success callback for cashpresso reference ID @reference_id.', ['@reference_id' => $reference_id]));
    }
    $verification_hash = $this->generateVerificationHash($payment, $request_content['status']);
    if ($verification_hash != $request_content['verificationHash']) {
      throw new InvalidRequestException($this->t('Verification hash does not match for cashpresso reference ID @reference_id.', ['@reference_id' => $reference_id]));
    }

    switch ($request_content['status']) {
      case CashpressoGatewayInterface::REMOTE_STATUS_CANCELLED:
        $void_transition = $payment->getState()->getWorkflow()->getTransition('void');
        $payment->getState()->applyTransition($void_transition);
        $payment->save();
        break;

      case CashpressoGatewayInterface::REMOTE_STATUS_SUCCESS:
        $capture_transition = $payment->getState()->getWorkflow()->getTransition('capture');
        $payment->getState()->applyTransition($capture_transition);
        $payment->save();
        break;

      case CashpressoGatewayInterface::REMOTE_STATUS_TIMEOUT:
        $expire_transition = $payment->getState()->getWorkflow()->getTransition('expire');
        $payment->getState()->applyTransition($expire_transition);
        $payment->save();
        break;

      default:
        throw new InvalidRequestException($this->t('Invalid transaction status returned for cashpresso reference ID @reference_id.', ['@reference_id' => $reference_id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentInstructions(PaymentInterface $payment) {
    return [
      '#type' => 'inline_template',
      '#template' => '<div id="cashpresso-payment-instructions"><h3>{{ header_text }}</h3><p>{{ intro_text }}</p><script type="text/javascript">c2SuccessCallback = function() { document.getElementById("cashpresso-payment-instructions").innerHTML = "<h3>{{ thankyou_header }}</h3><p>{{ thankyou_text }}</p>" }</script><script id="c2PostCheckoutScript" type="text/javascript" src="https://my.cashpresso.com/ecommerce/v2/checkout/c2_ecom_post_checkout.all.min.js" defer data-c2-partnerApiKey="{{ api_key }}" data-c2-purchaseId="{{ purchase_id }}" data-c2-mode="{{ mode }}" data-c2-locale="{{ locale }}" data-c2-successCallback="true"></script></div>',
      '#context' => [
        'api_key' => $this->getApiKey(),
        'purchase_id' => $payment->getRemoteId(),
        'mode' => $this->getMode(),
        'locale' => $this->languageManager->getCurrentLanguage()->getId(),
        'header_text' => $this->t('Pay for the order to complete the purchase'),
        'intro_text' => $this->t('Complete your order by paying for your purchase with cashpresso. By clicking on "Pay Now" a window opens and you can complete your purchase with cashpresso.'),
        'thankyou_header' => $this->t('Thank you for your purchase!'),
        'thankyou_text' => $this->t('You have successfully completed the order and paid for your purchase with cashpresso.'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function authorizePayment(PaymentInterface $payment, $cashpresso_token) {
    $endpoint = sprintf('%s/backend/ecommerce/v2/buy', $this->getActiveEndpointUrl());
    $order = $payment->getOrder();

    $valid_until = $this->time->getRequestTime() + ($this->configuration['order_valid_time'] * 60 * 60);
    $valid_until_formatted = $this->dateFormatter->format($valid_until, 'custom', DATE_ATOM);

    $params = [
      'partnerApiKey' => $this->getApiKey(),
      'c2EcomId' => $cashpresso_token,
      'amount' => $payment->getAmount()->getNumber(),
      'currency' => $payment->getAmount()->getCurrencyCode(),
      'verificationHash' => $this->generateVerificationHashForCheckoutRequest($payment),
      'validUntil' => $valid_until_formatted,
      'bankUsage' => $payment->getOrderId(),
      'interestFreeDaysMerchant' => $this->getInterestFreeDaysMerchant(),
      'callbackUrl' => $this->getNotifyUrl()->toString(),
      'language' => $this->languageManager->getCurrentLanguage()->getId(),
    ];

    $invoice_address = $this->convertToCashpressoAddressArray($order->getBillingProfile());
    if (!empty($invoice_address)) {
      $params['invoiceAddress'] = $invoice_address;
    }

    $delivery_address = $this->convertToCashpressoAddressArray($this->getShippingProfile($order));
    if (!empty($delivery_address)) {
      $params['deliveryAddress'] = $delivery_address;
    }

    $customer = $order->getCustomer();
    if ($customer && $customer->isAuthenticated()) {
      $params['merchantCustomerId'] = $customer->id();
    }

    $basket = [];
    foreach ($order->getItems() as $item) {
      $basket[] = [
        'description' => $item->getTitle(),
        'amount' => $item->getAdjustedUnitPrice()->getNumber(),
        'times' => (int) $item->getQuantity(),
      ];
    }
    $params['basket'] = $basket;

    try {
      $response = $this->httpClient->post($endpoint, [RequestOptions::JSON => $params]);
      $json_response = Json::decode($response->getBody());
      if (empty($json_response['success'])) {
        // Error.
        $msg = !empty($json_response['error']) ? sprintf('%s: %s', $json_response['error']['type'], $json_response['error']['description']) : $this->t('Cashpresso purchase request failed.');
        $this->logger->error($msg);
        return FALSE;
      }
      $purchase_id = $json_response['purchaseId'];
      $payment->setRemoteId($purchase_id);
      $payment->setExpiresTime($valid_until);
      $authorize_transition = $payment->getState()->getWorkflow()->getTransition('authorize');
      $payment->getState()->applyTransition($authorize_transition);
      $order->setData('cashpresso', [
        'token' => $cashpresso_token,
        'purchase_id' => $purchase_id,
      ]);
      $payment->save();
      $order->save();
      return TRUE;
    }
    catch (RequestException $request_exception) {
      $msg = $request_exception->getMessage() ?: $this->t('Cashpresso purchase request failed.');
      $this->logger->error($msg);
    }
    catch (\Exception $ex) {
      $msg = $ex->getMessage() ?: $this->t('Cashpresso purchase request failed.');
      $this->logger->error($msg);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveEndpointUrl() {
    return $this->getMode() == 'test' ? 'https://test.cashpresso.com/rest' : 'https://backend.cashpresso.com/rest';
  }

  /**
   * {@inheritdoc}
   */
  public function generateVerificationHashForCheckoutRequest(PaymentInterface $payment) {
    $parts = [
      $this->configuration['secret'],
      $this->toMinorUnits($payment->getAmount()),
      $this->getInterestFreeDaysMerchant(),
      $payment->getOrderId(),
      '',
    ];
    return hash('sha512', implode(';', $parts));
  }

  /**
   * {@inheritdoc}
   */
  public function generateVerificationHash(PaymentInterface $payment, $cashpresso_status) {
    $parts = [
      $this->configuration['secret'],
      $cashpresso_status,
      $payment->getRemoteId(),
      $payment->getOrderId(),
    ];
    return hash('sha512', implode(';', $parts));
  }

  /**
   * {@inheritdoc}
   */
  public function fetchPartnerInfo($force_update = FALSE) {
    $partner_info = $force_update ? NULL : $this->partnerInfoStore->getPartnerInfo();

    if (empty($partner_info)) {
      $endpoint = sprintf('%s/backend/ecommerce/v2/partnerInfo', $this->getActiveEndpointUrl());

      $params = [
        'partnerApiKey' => $this->getApiKey(),
      ];

      try {
        $response = $this->httpClient->post($endpoint, [RequestOptions::JSON => $params]);
        $json_response = Json::decode($response->getBody());
        if (empty($json_response['success'])) {
          // Error.
          $msg = !empty($json_response['error']) ? sprintf('%s: %s', $json_response['error']['type'], $json_response['error']['description']) : $this->t('Cashpresso partner info request failed.');
          $this->logger->error($msg);
          return NULL;
        }
        $partner_info = PartnerInfo::fromArray($json_response);
        $this->partnerInfoStore->setPartnerInfo($partner_info);
      }
      catch (RequestException $request_exception) {
        $msg = $request_exception->getMessage() ?: $this->t('Cashpresso partner info request failed.');
        $this->logger->error($msg);
        return NULL;
      }
      catch (\Exception $ex) {
        $msg = $ex->getMessage() ?: $this->t('Cashpresso partner info request failed.');
        $this->logger->error($msg);
        return NULL;
      }
    }
    return $partner_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiKey() {
    return $this->configuration['api_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getInterestFreeDaysMerchant() {
    return $this->configuration['interest_free_days_merchant'];
  }

  /**
   * Converts the given profile to a cashpresso compatible address array.
   *
   * @param \Drupal\profile\Entity\ProfileInterface|null $profile
   *   The profile. Empty or address-less profiles will return an empty array.
   *
   * @return string[]
   *   An array containing the keys 'country', 'zip' and 'street'.
   */
  protected function convertToCashpressoAddressArray(ProfileInterface $profile = NULL) {
    if (empty($profile) || !$profile->hasField('address') || $profile->get('address')->isEmpty()) {
      return [];
    }
    /** @var \Drupal\address\AddressInterface $address */
    $address = $profile->address->first();
    return [
      'country' => $address->getCountryCode(),
      'zip' => $address->getPostalCode(),
      'street' => $address->getAddressLine1(),
    ];
  }

  /**
   * Gets the shipping profile, if exists.
   *
   * The function safely checks for the existence of the 'shipments' field,
   * which is installed by commerce_shipping. If the field does not exist or is
   * empty, NULL will be returned.
   *
   * The shipping profile is assumed to be the same for all shipments.
   * Therefore, it is taken from the first found shipment, or created from
   * scratch if no shipments were found.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The shipping profile.
   */
  protected function getShippingProfile(OrderInterface $order) {
    if ($order->hasField('shipments')) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      foreach ($order->shipments->referencedEntities() as $shipment) {
        return $shipment->getShippingProfile();
      }
    }

    return NULL;
  }

}
