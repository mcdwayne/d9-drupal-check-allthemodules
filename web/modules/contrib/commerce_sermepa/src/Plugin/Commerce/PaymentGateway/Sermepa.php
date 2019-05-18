<?php

namespace Drupal\commerce_sermepa\Plugin\Commerce\PaymentGateway;

use CommerceRedsys\Payment\Sermepa as SermepaApi;
use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\HasPaymentInstructionsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Sermepa/Redsýs payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_sermepa",
 *   label = "Sermepa/Redsýs",
 *   display_label = "Pay with credit or debit card",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_sermepa\PluginForm\OffsiteRedirect\SermepaForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "maestro",
 *     "mastercard",
 *     "visa"
 *   },
 * )
 */
class Sermepa extends OffsitePaymentGatewayBase implements HasPaymentInstructionsInterface {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The locking layer instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new Sermepa object.
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
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The locking layer instance.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route
   *   The current route match service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, LoggerChannelFactoryInterface $logger_channel_factory, LockBackendInterface $lock, CurrentRouteMatch $current_route) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->currentRouteMatch = $current_route;
    $this->lock = $lock;
    $this->logger = $logger_channel_factory->get('commerce_sermepa');
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
      $container->get('lock'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_name' => '',
      'merchant_code' => '',
      'merchant_group' => '',
      'merchant_password' => '',
      'merchant_terminal' => '',
      'merchant_paymethods' => [],
      'merchant_consumer_language' => '001',
      'currency' => '978',
      'transaction_type' => '0',
      'instructions' => [
        'value' => '',
        'format' => 'plain_text',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);

    // Providing a default for merchant_paymethods in defaultConfiguration()
    // doesn't work because NestedArray::mergeDeep causes duplicates.
    if (!isset($this->configuration['merchant_paymethods'])) {
      $this->configuration['merchant_paymethods'][] = 'C';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configuration = $this->getConfiguration();

    $form['merchant_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant name'),
      '#default_value' => $configuration['merchant_name'],
      '#size' => 60,
      '#maxlength' => SermepaApi::getMerchantNameMaxLength(),
      '#required' => TRUE,
    ];
    $form['merchant_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant code'),
      '#default_value' => $configuration['merchant_code'],
      '#size' => 60,
      '#maxlength' => SermepaApi::getMerchantCodeMaxLength(),
      '#required' => TRUE,
    ];
    $form['merchant_group'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant group'),
      '#default_value' => $configuration['merchant_group'],
      '#size' => 60,
      '#maxlength' => SermepaApi::getMerchantGroupMaxLength(),
      '#required' => FALSE,
    ];
    $form['merchant_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SHA256 merchant password'),
      '#default_value' => $configuration['merchant_password'],
      '#size' => 60,
      '#maxlength' => SermepaApi::getMerchantPasswordMaxLength(),
      '#required' => TRUE,
    ];
    $form['merchant_terminal'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant terminal'),
      '#default_value' => $configuration['merchant_terminal'],
      '#size' => 5,
      '#maxlength' => SermepaApi::getMerchantTerminalMaxLength(),
      '#required' => TRUE,
    ];
    $form['merchant_paymethods'] = [
      '#type' => 'select',
      '#title' => $this->t('Merchant payment methods'),
      '#options' => SermepaApi::getAvailablePaymentMethods(),
      '#default_value' => $configuration['merchant_paymethods'],
      '#size' => 8,
      '#required' => TRUE,
      '#multiple' => TRUE,
    ];
    $form['merchant_consumer_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Merchant consumer language'),
      '#options' => SermepaApi::getAvailableConsumerLanguages(),
      '#default_value' => $configuration['merchant_consumer_language'],
      '#required' => TRUE,
    ];
    $form['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => $this->getAvailableCurrencies(),
      '#default_value' => $configuration['currency'],
      '#required' => TRUE,
    ];
    $form['transaction_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Transaction type'),
      '#options' => SermepaApi::getAvailableTransactionTypes(),
      '#default_value' => $configuration['transaction_type'],
      '#required' => TRUE,
    ];
    $form['instructions'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Payment instructions'),
      '#description' => $this->t('Shown the end of checkout, after the customer has placed their order.'),
      '#default_value' => $configuration['instructions']['value'],
      '#format' => $configuration['instructions']['format'],
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

      $values['merchant_paymethods'] = array_filter($values['merchant_paymethods']);

      $configuration = $this->getConfiguration();
      foreach ($this->defaultConfiguration() as $name => $default_value) {
        $configuration[$name] = $values[$name];
      }

      $this->setConfiguration($configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentInstructions(PaymentInterface $payment) {
    $configuration = $this->getConfiguration();

    $instructions = [];
    if (!empty($configuration['instructions']['value'])) {
      $instructions = [
        '#type' => 'processed_text',
        '#text' => $configuration['instructions']['value'],
        '#format' => $configuration['instructions']['format'],
      ];
    }

    return $instructions;
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // Do not process the notification if the payment is being processed.
    /* @see \Drupal\commerce_sermepa\Plugin\Commerce\PaymentGateway\Sermepa::onNotify() */
    if ($this->lock->lockMayBeAvailable($this->getLockName($order))) {
      $this->processRequest($request, $order);
    }
    else {
      // Wait for onNotify request that is doing this work, this occurs on
      // asynchronous calls, when  onNotify and onReturn can collide.
      $this->lock->wait($this->getLockName($order));
    }

    // We could have an outdated order, just reload it and check the states.
    // @TODO Change this when #3043180 is fixed.
    /* @see https://www.drupal.org/project/commerce/issues/3043180 */
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $updated_order = $order_storage->loadUnchanged($order->id());

    // If we have different states is because the payment has been validated
    // on the onNotify method and we need to force the redirection to the next
    // step or it the order will be placed twice.
    if ($updated_order->getState()->getId() != $order->getState()->getId()) {
      // Get the current checkout step and calculate the next step.
      $step_id = $this->currentRouteMatch->getParameter('step');
      /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
      $checkout_flow = $order->get('checkout_flow')->first()->get('entity')->getTarget()->getValue();
      $checkout_flow_plugin = $checkout_flow->getPlugin();
      $redirect_step_id = $checkout_flow_plugin->getNextStepId($step_id);

      throw new NeedsRedirectException(Url::fromRoute('commerce_checkout.form', [
        'commerce_order' => $updated_order->id(),
        'step' => $redirect_step_id,
      ])->toString());
    }

    $this->messenger()->addStatus($this->t('Your payment has been completed successfully.'));
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    try {
      // At this point we can not check if the order is locked, we do not have
      // the order, we just continue and check if it is locked when we have the
      // order.
      $this->processRequest($request);
    }
    catch (\Exception $exception) {
      // Nothing to do. ::processRequest throws exceptions if the payment can
      // not be processed, and returns an error 500 to Sermepa/Redsýs.
    }
  }

  /**
   * Processes the notification request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if the payment is valid, otherwise FALSE.
   *
   * @throws \CommerceRedsys\Payment\SermepaException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function processRequest(Request $request, OrderInterface $order = NULL) {
    // Capture received data values.
    $feedback = [
      'Ds_SignatureVersion' => $request->get('Ds_SignatureVersion'),
      'Ds_MerchantParameters' => $request->get('Ds_MerchantParameters'),
      'Ds_Signature' => $request->get('Ds_Signature'),
    ];

    if (empty($feedback['Ds_SignatureVersion']) || empty($feedback['Ds_MerchantParameters']) || empty($feedback['Ds_Signature'])) {
      throw new PaymentGatewayException('Bad feedback response, missing feedback parameter.');
    }

    // Get the payment method settings.
    $payment_method_settings = $this->getConfiguration();

    // Create a new instance of the Sermepa library and initialize it.
    $gateway = new SermepaApi($payment_method_settings['merchant_name'], $payment_method_settings['merchant_code'], $payment_method_settings['merchant_terminal'], $payment_method_settings['merchant_password'], $this->getMode());

    // Get order number from feedback data and compare it with the order object
    // argument or loaded.
    $parameters = $gateway->decodeMerchantParameters($feedback['Ds_MerchantParameters']);
    $order_id = $parameters['Ds_MerchantData'];

    if ($order === NULL) {
      $order_storage = $this->entityTypeManager->getStorage('commerce_order');
      $order = $order_storage->load($order_id);
    }
    if ($order === NULL || $order->id() != $order_id) {
      $this->logger->warning('The received order ID and the argument order ID does not match.');
    }

    // The onNotify and onReturn methods can collide causing a race condition.
    if ($this->lock->acquire($this->getLockName($order))) {
      // Validate feedback values.
      if (!$gateway->validSignatures($feedback)) {
        $this->lock->release($this->getLockName($order));
        throw new PaymentGatewayException('Bad feedback response, signatures does not match.');
      }

      if ($gateway->authorizedResponse($parameters['Ds_Response'])) {
        /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

        // Check if the payment has been processed, we could have multiple
        // payments.
        $payments = $payment_storage->getQuery()
          ->condition('payment_gateway', $this->entityId)
          ->condition('order_id', $order->id())
          ->condition('remote_id', $parameters['Ds_AuthorisationCode'])
          ->execute();

        if (empty($payments)) {
          /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
          $payment = $payment_storage->create([
            'state' => 'authorization',
            'amount' => $order->getTotalPrice(),
            'payment_gateway' => $this->entityId,
            'order_id' => $order->id(),
            'test' => $this->getMode() == 'test',
            'remote_id' => $parameters['Ds_AuthorisationCode'],
            'remote_state' => SermepaApi::handleResponse($parameters['Ds_Response']),
            'authorized' => $this->time->getRequestTime(),
          ]);
          $status_mapping = $this->getStatusMapping();

          if (isset($status_mapping[$this->getConfiguration()['transaction_type']])) {
            $payment->setState($status_mapping[$this->getConfiguration()['transaction_type']]);
          }

          if (!$order->get('payment_method')->isEmpty()) {
            /** @var \Drupal\Core\Entity\Plugin\DataType\EntityReference $credit_card */
            $credit_card = $order->get('payment_method')
              ->first()
              ->get('entity')
              ->getTarget()
              ->getValue();
            $payment->set('payment_method', $credit_card)->save();
          }
          $payment->save();
        }

        $this->lock->release($this->getLockName($order));

        return TRUE;
      }

      $this->lock->release($this->getLockName($order));

      throw new PaymentGatewayException('Failed attempt, the payment could not be made.');
    }
  }

  /**
   * Returns only the active currencies.
   *
   * We don't want allow to configure a currency which is not active in our
   * site.
   *
   * @return array
   *   The available currencies array values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAvailableCurrencies() {
    // Get the supported currencies.
    $sermepa_currencies = SermepaApi::getAvailableCurrencies();

    $currency_storage = $this->entityTypeManager->getStorage('commerce_currency');

    // Use the supported currencies to load only the enable currencies.
    $currency_ids = $currency_storage
      ->getQuery()
      ->condition('numericCode', array_keys($sermepa_currencies), 'IN')
      ->execute();

    $available_currencies = [];
    if ($currency_ids) {
      /** @var \Drupal\commerce_price\Entity\CurrencyInterface[] $enabled_currencies */
      $enabled_currencies = $currency_storage->loadMultiple($currency_ids);

      // Prepare the currency array to use in the form element.
      foreach ($enabled_currencies as $currency) {
        $available_currencies[$currency->getNumericCode()] = $currency->getName();
      }
    }

    return $available_currencies;
  }

  /**
   * Returns a mapping of Sermepa/Redsýs payment statuses to payment states.
   *
   * @param string $status
   *   (optional) The Sermepa/Redsýs payment status.
   *
   * @return array|string
   *   An array containing the Sermepa/Redsýs remote statuses as well as their
   *   corresponding states. if $status is specified, the corresponding state
   *   is returned.
   */
  protected function getStatusMapping($status = NULL) {
    /* @see \CommerceRedsys\Payment\Sermepa::getAvailableTransactionTypes */
    /* @see commerce/modules/payment/commerce_payment.workflows.yml */
    $mapping = [
      // Sermepa/Redsýs: Authorization.
      '0' => 'completed',
      // Sermepa/Redsýs: Pre-authorization.
      '1' => 'authorization',
      // Sermepa/Redsýs: Confirmation of preauthorization.
      '2' => 'authorization',
      // Sermepa/Redsýs: Automatic return.
      '3' => 'refunded',
      // Sermepa/Redsýs: Recurring transaction.
      '5' => 'completed',
      // Sermepa/Redsýs: Successive transaction.
      '6' => 'completed',
      // Sermepa/Redsýs: Pre-authentication.
      '7' => 'authorization',
      // Sermepa/Redsýs: Confirmation of pre-authentication.
      '8' => 'authorization',
      // Sermepa/Redsýs: Annulment of preauthorization.
      '9' => 'authorization_expired',
      // Sermepa/Redsýs: Authorization delayed.
      'O' => 'authorization',
      // Sermepa/Redsýs: Confirmation of authorization in deferred.
      'P' => 'authorization',
      // Sermepa/Redsýs: Delayed authorization Rescission.
      'Q' => 'authorization',
      // Sermepa/Redsýs: Initial recurring deferred released.
      'R' => 'completed',
      // Sermepa/Redsýs: Successively recurring deferred released.
      'S' => 'completed',
    ];

    // If a status was passed, return its corresponding payment state.
    if (isset($status) && isset($mapping[$status])) {
      return $mapping[$status];
    }

    return $mapping;
  }

  /**
   * Returns the lock name.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return string
   *   The built lock name.
   */
  protected function getLockName(OrderInterface $order) {
    return 'commerce_sermepa_process_request_' . $order->uuid();
  }

}
