<?php

namespace Drupal\commerce_hyperpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_hyperpay\Event\AlterHyperpayAmountEvent;
use Drupal\commerce_hyperpay\Event\HyperpayPaymentEvents;
use Drupal\commerce_hyperpay\Transaction\Status\Factory;
use Drupal\commerce_hyperpay\Transaction\Status\Rejected;
use Drupal\commerce_hyperpay\Transaction\Status\SuccessOrPending;
use Drupal\commerce_order\Entity\OrderInterface;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Hyperpay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "hyperpay_payment",
 *   label = @Translation("Hyperpay Payment"),
 *   display_label = @Translation("Hyperpay Payment"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_hyperpay\PluginForm\HyperpayForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */

class HyperPay extends OffsitePaymentGatewayBase implements HyperPayInterface {
  
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
   * Constructs a new HyperPay object.
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
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The price rounder.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, EventDispatcherInterface $event_dispatcher, Client $http_client, RounderInterface $rounder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    
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
      '#title' => $this->t('Entity ID'),
      '#default_value' => $this->configuration['entity_id'],
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
      $this->configuration['user_id'] = $values['user_id'];
      $this->configuration['password'] = $values['password'];
      $this->configuration['entity_id'] = $values['entity_id'];
      $this->configuration['mode'] = $values['mode'];
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function prepareCheckout(array $params = []) {
    $checkout_id = NULL;
    $base_url = $this->getApiUrl();
    $url = $base_url . '/v1/checkouts';
    $params += [
      'authentication.userId' => $this->configuration['user_id'],
      'authentication.password' => $this->configuration['password'],
      'authentication.entityId' => $this->configuration['entity_id'],
    ];
    try {
      $response = $this->httpClient->post($url, [RequestOptions::FORM_PARAMS => $params]);
      $json_response = json_decode($response->getBody(), TRUE);
      if (!empty($json_response['id'])) {
        $checkout_id = $json_response['id'];
      }
      else {
        throw new InvalidRequestException($this->t('Cannot prepare Hyperpay checkout: could not retrieve checkout ID.'));
      }
    }
    catch (RequestException $request_exception) {
      throw new InvalidResponseException($this->t('Cannot prepare Hyperpay checkout due to exception: @error', ['@error' => $request_exception->getMessage()]));
    }
    catch (\Exception $ex) {
      throw new InvalidResponseException($this->t('Cannot prepare Hyperpay checkout due to exception: @error', ['@error' => $ex->getMessage()]));
    }
    
    return $checkout_id;
  }
  
  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    parent::onReturn($order, $request);
    
    $resource_path = $request->query->get('resourcePath');
    if (empty($resource_path)) {
      throw new PaymentGatewayException('No resource path found in query string on Hyperpay payment return.');
    }
    $checkout_id = $request->query->get('id');
    if (empty($checkout_id)) {
      throw new PaymentGatewayException('No checkout ID specified in query string on Hyperpay payment return.');
    }
    
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->paymentStorage->loadByRemoteId($checkout_id);
    if (empty($payment)) {
      throw new PaymentGatewayException($this->t('No pre-authorized payment could be found for the checkout ID specified by Hyperpay payment return callback (ID: @checkout_id / resource path: @resource_path)',
        [
          '@checkout_id' => $checkout_id,
          '@resource_path' => $resource_path,
        ])
      );
    }
    
    $base_url = $this->getApiUrl();
    $url = $base_url . $resource_path;
    $params = [
      'authentication.userId' => $this->configuration['user_id'],
      'authentication.password' => $this->configuration['password'],
      'authentication.entityId' => $this->configuration['entity_id'],
    ];
    try {
      $response = $this->httpClient->get($url, [RequestOptions::QUERY => $params]);
      $json_response = json_decode($response->getBody(), TRUE);
      
      if (empty($json_response['id'])) {
        throw new InvalidResponseException($this->t('Unable to identify Hyperpay payment (resource path: @resource_path)', ['@resource_path' => $resource_path]));
      }
      
      // The ID we receive in this response is different to the checkout ID.
      // The checkout ID was only a temporary remote value, in order to be able
      // to fetch the payment in this callback. Now, we have received the real
      // remote ID and will use it.
      $remote_payment_id = $json_response['id'];
      $payment->setRemoteId($remote_payment_id);
      if ((int) $payment->getOrderId() !== (int) $order->id()) {
        throw new InvalidResponseException($this->t('The order ID used on the payment return callback (@request_order_id) does not match the parent order ID of the given payment (@payment_order_id). (resource path: @resource_path)',
          [
            '@request_order_id' => $order->id(),
            '@payment_order_id' => $payment->getOrderId(),
            '@resource_path' => $resource_path,
          ]
        ));
      }
      
      if (!isset($json_response['amount']) && !isset($json_response['currency'])) {
        throw new InvalidResponseException($json_response['result']['description']);
      }
      
      $paid_amount = new Price($json_response['amount'], $json_response['currency']);
      if (!$paid_amount->equals($payment->getAmount())) {
        throw new InvalidResponseException($this->t('The payment amount deviates from the expected value (given: @given_currency @given_amount / expected: @expected_currency @expected_amount).',
          [
            '@given_currency'    => $paid_amount->getCurrencyCode(),
            '@given_amount'      => $paid_amount->getNumber(),
            '@expected_currency' => $payment->getAmount()->getCurrencyCode(),
            '@expected_amount'   => $payment->getAmount()->getNumber(),
          ]
        ));
      }
      
      $payment_status = Factory::newInstance($json_response['result']['code'], $json_response['result']['description']);
      if (empty($payment_status)) {
        throw new PaymentGatewayException($this->t('Received unknown payment status @code for checkout ID @remote_id (@description).',
          [
            '@code' => $json_response['result']['code'],
            '@remote_id' => $remote_payment_id,
            '@description' => $json_response['result']['description'],
          ]
        ));
      }
      
      $payment->setRemoteState($payment_status->getCode());
      if ($payment_status instanceof SuccessOrPending) {
        $capture_transition = $payment->getState()->getWorkflow()->getTransition('capture');
        $payment->getState()->applyTransition($capture_transition);
      }
      elseif ($payment_status instanceof Rejected) {
        $void_transition = $payment->getState()->getWorkflow()->getTransition('void');
        $payment->getState()->applyTransition($void_transition);
      }
      $payment->save();
    }
    catch (RequestException $request_exception) {
      throw new InvalidResponseException($this->t('Error occurred on calling the specified Hyperpay resource path @resource_path with message: @msg', ['@resource_path' => $resource_path, '@msg' => $request_exception->getMessage()]));
    }
    catch (\Exception $ex) {
      throw new InvalidResponseException($this->t('Error occurred on calling the specified Hyperpay resource path @resource_path with message: @msg', ['@resource_path' => $resource_path, '@msg' => $ex->getMessage()]));
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function getApiUrl() {
    if ($this->getMode() == 'test') {
      return 'https://test.oppwa.com';
    }
    else {
      return 'https://oppwa.com';
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function calculateCheckoutIdExpireTime($request_time = NULL) {
    if (empty($request_time)) {
      $request_time = $this->time->getRequestTime();
    }
    // A checkout ID is valid for 30 minutes.
    // @see https://hyperpay.docs.oppwa.com/support/widget
    return $request_time + (30 * 60);
  }
  
  /**
   * {@inheritdoc}
   */
  public function getPayableAmount(OrderInterface $order) {
    $event = new AlterHyperpayAmountEvent($order);
    $this->eventDispatcher->dispatch(HyperpayPaymentEvents::ALTER_AMOUNT, $event);
    $payment_amount = $event->getPaymentAmount();
    
    return $this->rounder->round($payment_amount);
  }
}
