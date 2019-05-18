<?php

namespace Drupal\commerce_opp\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_opp\BrandRepositoryInterface;
use Drupal\commerce_opp\Event\AlterPaymentAmountEvent;
use Drupal\commerce_opp\Event\OpenPaymentPlatformPaymentEvents;
use Drupal\commerce_opp\Transaction\Status\Factory;
use Drupal\commerce_opp\Transaction\Status\Pending;
use Drupal\commerce_opp\Transaction\Status\Rejected;
use Drupal\commerce_opp\Transaction\Status\SuccessOrPending;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a base class for COPYandPAY payment gateways.
 *
 * For the sake of stricter configuration, we define different payment gateway
 * plugins for each of card, bank and virtual account brands. The differences
 * however only affect configuration options and class annotations.
 */
abstract class CopyAndPayBase extends OffsitePaymentGatewayBase implements CopyAndPayInterface {

  /**
   * The brand repository.
   *
   * @var \Drupal\commerce_opp\BrandRepositoryInterface
   */
  protected $brandRepository;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The payment storage.
   *
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

  /**
   * The price rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * Constructs a new CopyAndPayBase object.
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
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \GuzzleHttp\Client $http_client
   *   The http client.
   * @param \Drupal\commerce_opp\BrandRepositoryInterface $brand_repository
   *   The brand repository.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The price rounder.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, EventDispatcherInterface $event_dispatcher, Client $http_client, BrandRepositoryInterface $brand_repository, RounderInterface $rounder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->brandRepository = $brand_repository;
    $this->eventDispatcher = $event_dispatcher;
    $this->httpClient = $http_client;
    $this->paymentStorage = $entity_type_manager->getStorage('commerce_payment');
    $this->rounder = $rounder;
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
      $container->get('event_dispatcher'),
      $container->get('http_client'),
      $container->get('commerce_opp.brand_repository'),
      $container->get('commerce_price.rounder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'user_id' => '',
      'password' => '',
      'entity_id' => '',
      'show_amount' => TRUE,
      'host_live' => CopyAndPayInterface::DEFAULT_HOST_LIVE,
      'host_test' => CopyAndPayInterface::DEFAULT_HOST_TEST,
      'brands' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User ID'),
      '#default_value' => $this->configuration['user_id'],
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->configuration['password'],
      '#required' => TRUE,
    ];

    $form['entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The entity for the request'),
      '#description' => $this->t("By default this is the channel's ID. It can be the division, merchant or channel identifier. Division is for requesting registrations only, merchant only in combination with channel dispatching, i.e. channel is the default for sending payment transactions."),
      '#default_value' => $this->configuration['entity_id'],
      '#required' => TRUE,
    ];

    $form['show_amount'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show amount to be paid on payment page'),
      '#default_value' => $this->configuration['show_amount'],
    ];

    $brand_description = !$this->allowMultipleBrands() ? $this->t('If you want to support multiple brands, you need to configure separate gateways for each brand, as otherwise multiple COPYandPAY widgets would be rendered one after another on the payment page, which is very confusing. It is better to select the payment  method first in the checkout step and have a single widget rendered on the payment page then.') : '';
    $form['brands'] = [
      '#type' => 'details',
      '#title' => $this->t("Supported brands"),
      '#description' => $brand_description ? '<p>' . $brand_description . '</p>' : '',
      '#open' => TRUE,
    ];

    $form['brands']['brands'] = [
      '#type' => 'select',
      '#title' => $this->t("Supported brands"),
      '#required' => TRUE,
      '#multiple' => $this->allowMultipleBrands(),
      '#options' => $this->getBrandOptions(),
      '#default_value' => isset($this->configuration['brands']) ? $this->configuration['brands'] : '',
      '#empty_value' => '',
      '#attributes' => ['size' => 10],
    ];

    $form['host'] = [
      '#type' => 'details',
      '#title' => $this->t("Gateway base URLs"),
      '#open' => FALSE,
    ];

    $form['host']['host_live'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host URL (live environment)'),
      '#description' => $this->t('The host URL for the live environment (defaults to %host_url)', ['%host_url' => CopyAndPayInterface::DEFAULT_HOST_LIVE]),
      '#default_value' => $this->getLiveHostUrl(),
      '#required' => TRUE,
    ];

    $form['host']['host_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host URL (test environment)'),
      '#description' => $this->t('The host URL for the test environment (defaults to %host_url)', ['%host_url' => CopyAndPayInterface::DEFAULT_HOST_TEST]),
      '#default_value' => $this->getTestHostUrl(),
      '#required' => TRUE,
    ];

    $form['host']['test_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Test mode'),
      '#description' => $this->t('"Internal" causes transactions to be sent to our simulators, which is useful when switching to the live endpoint for connectivity testing. "External" causes test transactions to be forwarded to the processor\'s test system for \'end-to-end\' testing'),
      '#options' => [
        'INTERNAL' => $this->t('Internal'),
        'EXTERNAL' => $this->t('External'),
      ],
      '#default_value' => isset($this->configuration['test_mode']) ? $this->configuration['test_mode'] : 'INTERNAL',
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
      $brands = $values['brands']['brands'];
      if (!empty($brands) && !is_array($brands)) {
        $brands = [$brands];
      }
      $this->configuration['user_id'] = $values['user_id'];
      $this->configuration['password'] = $values['password'];
      $this->configuration['entity_id'] = $values['entity_id'];
      $this->configuration['show_amount'] = $values['show_amount'];
      $this->configuration['brands'] = $brands;
      $this->configuration['host_live'] = $values['host']['host_live'];
      $this->configuration['host_test'] = $values['host']['host_test'];
      $this->configuration['test_mode'] = $values['host']['test_mode'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareCheckout(array $params = []) {
    $checkout_id = NULL;
    $base_url = $this->getActiveHostUrl();
    $url = $base_url . '/v1/checkouts';
    $params += [
      'authentication.userId' => $this->configuration['user_id'],
      'authentication.password' => $this->configuration['password'],
      'authentication.entityId' => $this->configuration['entity_id'],
    ];
    if ($this->getMode() == 'test') {
      $params['testMode'] = $this->configuration['test_mode'];
    }
    try {
      $response = $this->httpClient->post($url, [RequestOptions::FORM_PARAMS => $params]);
      $json_response = json_decode($response->getBody(), TRUE);
      if (!empty($json_response['id'])) {
        $checkout_id = $json_response['id'];
      }
      else {
        throw new InvalidRequestException($this->t('Cannot prepare OPP checkout: could not retrieve checkout ID.'));
      }
    }
    catch (RequestException $request_exception) {
      throw new InvalidResponseException($this->t('Cannot prepare OPP checkout due to exception: @error', ['@error' => $request_exception->getMessage()]));
    }
    catch (\Exception $ex) {
      throw new InvalidResponseException($this->t('Cannot prepare OPP checkout due to exception: @error', ['@error' => $ex->getMessage()]));
    }
    return $checkout_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionStatus(PaymentInterface $payment) {
    if (empty($payment->getRemoteId())) {
      throw new \InvalidArgumentException('The given payment entity has no remote ID set - cannot check OPP transaction status therefore.');
    }
    $checkout_id = $payment->getRemoteId();
    $base_url = $payment->getPaymentGatewayMode() == 'live' ? $this->getLiveHostUrl() : $this->getTestHostUrl();
    $url = $base_url . '/v1/checkouts/' . $checkout_id . '/payment';
    $params = [
      'authentication.userId' => $this->configuration['user_id'],
      'authentication.password' => $this->configuration['password'],
      'authentication.entityId' => $this->configuration['entity_id'],
    ];
    try {
      $response = $this->httpClient->get($url, [RequestOptions::QUERY => $params]);
      $json_response = json_decode($response->getBody(), TRUE);
      if (empty($json_response['id'])) {
        throw new InvalidResponseException($this->t('Unable to identify OPP payment (requested ID: @checkout_id)', ['@checkout_id' => $checkout_id]));
      }

      $brand_name = isset($json_response['paymentBrand']) ? $json_response['paymentBrand'] : NULL;
      $brand = $this->brandRepository->getBrand($brand_name);
      $payment_status = Factory::newInstance($json_response['id'], $json_response['result']['code'], $json_response['result']['description'], $brand);
      if (empty($payment_status)) {
        throw new PaymentGatewayException($this->t('Received unknown payment status @code for checkout ID @remote_id (@description).',
          [
            '@code' => $json_response['result']['code'],
            '@remote_id' => $json_response['id'],
            '@description' => $json_response['result']['description'],
          ]
        ));
      }

      if ($payment_status instanceof SuccessOrPending) {
        $paid_amount = new Price($json_response['amount'], $json_response['currency']);
        if (!$paid_amount->equals($payment->getAmount())) {
          throw new InvalidResponseException($this->t('The payment amount deviates from the expected value (given: @given_currency @given_amount / expected: @expected_currency @expected_amount).',
            [
              '@given_currency' => $paid_amount->getCurrencyCode(),
              '@given_amount' => $paid_amount->getNumber(),
              '@expected_currency' => $payment->getAmount()->getCurrencyCode(),
              '@expected_amount' => $payment->getAmount()->getNumber(),
            ]
          ));
        }
      }

      return $payment_status;
    }
    catch (RequestException $request_exception) {
      throw new InvalidResponseException($this->t('Error occurred on querying OPP transaction status for remote ID @checkout_id with message: @msg', ['@checkout_id' => $checkout_id, '@msg' => $request_exception->getMessage()]));
    }
    catch (\Exception $ex) {
      throw new InvalidResponseException($this->t('Error occurred on querying OPP transaction status for remote ID @checkout_id with message: @msg', ['@checkout_id' => $checkout_id, '@msg' => $ex->getMessage()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    parent::onReturn($order, $request);

    $resource_path = $request->query->get('resourcePath');
    if (empty($resource_path)) {
      throw new PaymentGatewayException('No resource path found in query string on Open Payment Platform payment return.');
    }
    $checkout_id = $request->query->get('id');
    if (empty($checkout_id)) {
      throw new PaymentGatewayException('No checkout ID specified in query string on Open Payment Platform payment return.');
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->paymentStorage->loadByRemoteId($checkout_id);
    if (empty($payment)) {
      throw new PaymentGatewayException($this->t('No pre-authorized payment could be found for the checkout ID specified by OPP payment return callback (ID: @checkout_id / resource path: @resource_path)',
        [
          '@checkout_id' => $checkout_id,
          '@resource_path' => $resource_path,
        ])
      );
    }

    if ((int) $payment->getOrderId() !== (int) $order->id()) {
      throw new InvalidResponseException($this->t('The order ID used on the payment return callback (@request_order_id) does not match the parent order ID of the given payment (@payment_order_id). (resource path: @resource_path)',
        [
          '@request_order_id' => $order->id(),
          '@payment_order_id' => $payment->getOrderId(),
          '@resource_path' => $resource_path,
        ]
      ));
    }

    $payment_status = $this->getTransactionStatus($payment);
    if ($payment_status->isAsyncPayment() && $payment_status instanceof Pending) {
      \Drupal::logger('commerce_opp')->info(sprintf('Skip processing of payment ID %s, as it is async type and in pending state.', $payment->id()));
      return;
    }

    // The ID we receive in this response is different to the checkout ID.
    // The checkout ID was only a temporary remote value, in order to be able to
    // fetch the payment in this callback. Now, we have received the real remote
    // ID and will use it.
    $payment->setRemoteId($payment_status->getId());
    $payment->setRemoteState($payment_status->getCode());
    if ($payment_status instanceof SuccessOrPending) {
      $capture_transition = $payment->getState()->getWorkflow()->getTransition('capture');
      $payment->getState()->applyTransition($capture_transition);
      $payment->save();
    }
    elseif ($payment_status instanceof Rejected) {
      $void_transition = $payment->getState()->getWorkflow()->getTransition('void');
      $payment->getState()->applyTransition($void_transition);
      $payment->save();
      throw new PaymentGatewayException($this->t('We could not complete your payment. Please try again or contact us if the problem persists.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    // Perform the refund request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $remote_id = $payment->getRemoteId();

    $base_url = $this->getActiveHostUrl();
    $url = $base_url . '/v1/payments/' . $remote_id;
    $params = [
      'authentication.userId' => $this->configuration['user_id'],
      'authentication.password' => $this->configuration['password'],
      'authentication.entityId' => $this->configuration['entity_id'],
      'amount' => $amount->getNumber(),
      'currency' => $amount->getCurrencyCode(),
      'paymentType' => 'RF',
    ];
    try {
      $response = $this->httpClient->post($url, [RequestOptions::FORM_PARAMS => $params]);
      $json_response = json_decode($response->getBody(), TRUE);
      if (empty($json_response['id'])) {
        throw new InvalidResponseException($this->t('Invalid refund request - response has no ID set.'));
      }

      $brand_name = isset($json_response['paymentBrand']) ? $json_response['paymentBrand'] : NULL;
      $brand = $this->brandRepository->getBrand($brand_name);
      $payment_status = Factory::newInstance($json_response['id'], $json_response['result']['code'], $json_response['result']['description'], $brand);
      if (empty($payment_status)) {
        throw new PaymentGatewayException($this->t('Received unknown payment status @code for refund request of ID @remote_id (@description).',
          [
            '@code' => $json_response['result']['code'],
            '@remote_id' => $remote_id,
            '@description' => $json_response['result']['description'],
          ]
        ));
      }

      if ($payment_status instanceof Rejected) {
        throw new HardDeclineException($this->t('Refund request was rejected: @description',
          [
            '@description' => $json_response['result']['description'],
          ]
        ));
      }
    }
    catch (RequestException $request_exception) {
      throw new InvalidResponseException($this->t('Cannot prepare OPP refund due to exception: @error', ['@error' => $request_exception->getMessage()]));
    }
    catch (\Exception $ex) {
      throw new InvalidResponseException($this->t('Cannot prepare OPP refund due to exception: @error', ['@error' => $ex->getMessage()]));
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
  public function getActiveHostUrl() {
    return $this->getMode() == 'test' ? $this->getTestHostUrl() : $this->getLiveHostUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getLiveHostUrl() {
    return !empty($this->configuration['host_live']) ? $this->configuration['host_live'] : CopyAndPayInterface::DEFAULT_HOST_LIVE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestHostUrl() {
    return !empty($this->configuration['host_test']) ? $this->configuration['host_test'] : CopyAndPayInterface::DEFAULT_HOST_TEST;
  }

  /**
   * {@inheritdoc}
   */
  public function getBrandIds() {
    return $this->configuration['brands'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBrands() {
    $brands = $this->getBrandIds();
    array_walk($brands, function (&$value, $key) {
      $value = $this->brandRepository->getBrand($key);
    });
    /** @var \Drupal\commerce_opp\Brand[] $brands */
    return $brands;
  }

  /**
   * {@inheritdoc}
   */
  public function isAmountVisible() {
    return !empty($this->configuration['show_amount']);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateCheckoutIdExpireTime($request_time = NULL) {
    if (empty($request_time)) {
      $request_time = $this->time->getRequestTime();
    }
    // A checkout ID is valid for 30 minutes.
    // @see https://docs.oppwa.com/support/widget
    return $request_time + (30 * 60);
  }

  /**
   * {@inheritdoc}
   */
  public function getPayableAmount(OrderInterface $order) {
    $event = new AlterPaymentAmountEvent($order);
    $this->eventDispatcher->dispatch(OpenPaymentPlatformPaymentEvents::ALTER_AMOUNT, $event);
    $payment_amount = $event->getPaymentAmount();
    return $this->rounder->round($payment_amount);
  }

  /**
   * Returns whether multiple brands are allowed within a single instance.
   *
   * Only credit cards are subject to be allowed to have multiple brands
   * configured in a single gateway because they are the only ones that can be
   * rendered in a single COPYandPAY widget. For every other payment type,
   * multiple COPYandPAY widgets would be rendered one after another on the
   * payment page, which is very confusing. It is better to select the payment
   * method first in the checkout step and have a single widget rendered on the
   * payment page then.
   *
   * @return bool
   *   TRUE, if the multiple brands are allowed to be configured within a single
   *   gateway instance, FALSE otherwise.
   */
  protected function allowMultipleBrands() {
    return FALSE;
  }

  /**
   * Returns allowed brand options suitable for select list in config form.
   *
   * @return array
   *   The brands labels, keyed by brand ID.
   */
  abstract protected function getBrandOptions();

}
