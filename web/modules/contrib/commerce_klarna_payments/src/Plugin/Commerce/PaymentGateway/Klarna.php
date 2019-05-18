<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_klarna_payments\Event\Events;
use Drupal\commerce_klarna_payments\Event\RequestEvent;
use Drupal\commerce_klarna_payments\KlarnaConnector;
use Drupal\commerce_klarna_payments\OptionsHelper;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Klarna\Rest\Transport\ConnectorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

/**
 * Provides the Klarna payments payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "klarna_payments",
 *   label = "Klarna Payments",
 *   display_label = "Klarna Payments",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_klarna_payments\PluginForm\OffsiteRedirect\KlarnaOffsiteForm",
 *   },
 * )
 */
final class Klarna extends OffsitePaymentGatewayBase implements SupportsAuthorizationsInterface, SupportsNotificationsInterface {

  use OptionsHelper;

  /**
   * The key used for EU region.
   *
   * @var string
   */
  protected const REGION_EU = 'eu';

  /**
   * The key used for NA region.
   *
   * @var string
   */
  protected const REGION_NA = 'na';

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The klarna connector.
   *
   * @var \Drupal\commerce_klarna_payments\KlarnaConnector
   */
  protected $connector;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    PaymentTypeManager $payment_type_manager,
    PaymentMethodTypeManager $payment_method_type_manager,
    TimeInterface $time,
    EventDispatcherInterface $eventDispatcher,
    LoggerInterface $logger,
    KlarnaConnector $connector
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $payment_type_manager,
      $payment_method_type_manager,
      $time
    );

    $this->eventDispatcher = $eventDispatcher;
    $this->connector = $connector;
    $this->logger = $logger;
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
      $container->get('logger.channel.commerce_klarna_payments'),
      $container->get('commerce_klarna_payments.connector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mode' => 'test',
      'username' => '',
      'password' => '',
      'options' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * Gets the Klarna connector.
   *
   * @return \Drupal\commerce_klarna_payments\KlarnaConnector
   *   The connector.
   */
  public function getKlarnaConnector() : KlarnaConnector {
    return $this->connector;
  }

  /**
   * Gets the logger.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger.
   */
  public function getLogger() : LoggerInterface {
    return $this->logger;
  }

  /**
   * Gets the live mode status.
   *
   * @return bool
   *   Boolean indicating whether we are operating in live mode.
   */
  public function isLive() : bool {
    return $this->configuration['mode'] === 'live';
  }

  /**
   * Gets the region.
   *
   * @return string
   *   The region.
   */
  public function getRegion() : string {
    return $this->configuration['region'];
  }

  /**
   * Gets the username.
   *
   * @return string
   *   The username.
   */
  public function getUsername() : string {
    return $this->configuration['username'];
  }

  /**
   * Gets the password.
   *
   * @return string
   *   The password.
   */
  public function getPassword() : string {
    return $this->configuration['password'];
  }

  /**
   * Gets the entity id (plugin id).
   *
   * @return string
   *   The entity id.
   */
  public function getEntityId() : string {
    return $this->entityId;
  }

  /**
   * Gets the return url.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $routeName
   *   The route.
   * @param array $arguments
   *   An additional arguments.
   *
   * @return string
   *   The URL.
   */
  public function getReturnUri(OrderInterface $order, string $routeName, array $arguments = []) : string {
    $arguments = array_merge($arguments, [
      'step' => 'payment',
      'commerce_order' => $order->id(),
      'commerce_payment_gateway' => $this->entityId,
    ]);

    return (new Url($routeName, $arguments, ['absolute' => TRUE]))
      ->toString();
  }

  /**
   * Gets the api uri.
   *
   * @return string
   *   The api uri.
   */
  public function getApiUri() : string {
    if ($this->getRegion() === static::REGION_EU) {
      return $this->isLive() ? ConnectorInterface::EU_BASE_URL : ConnectorInterface::EU_TEST_BASE_URL;
    }
    return $this->isLive() ? ConnectorInterface::NA_BASE_URL : ConnectorInterface::NA_TEST_BASE_URL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->configuration['username'],
    ];

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->configuration['password'],
    ];

    $form['region'] = [
      '#title' => $this->t('Region'),
      '#type' => 'select',
      '#options' => [
        static::REGION_EU => $this->t('Europe'),
        static::REGION_NA => $this->t('North America'),
      ],
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional options'),
      '#open' => FALSE,
    ];

    foreach ($this->getDefaultOptions() as $key => $title) {
      $form['options'][$key] = [
        '#type' => 'textfield',
        '#title' => $title,
        '#default_value' => $this->configuration['options'][$key] ?? NULL,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $this->configuration = $form_state->getValue($form['#parents']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    try {
      $klarnaOrder = $this->connector->getOrder($order);
    }
    catch (\Exception $e) {
      throw new PaymentGatewayException();
    }

    try {
      Assert::oneOf($klarnaOrder['status'], [
        'AUTHORIZED',
        'PART_CAPTURED',
        'CAPTURED',
      ]);
    }
    catch (\InvalidArgumentException $e) {
      throw new PaymentGatewayException($this->t('Order is in invalid state [@state]', [
        '@state' => $klarnaOrder['status'],
      ]));
    }
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $payment_storage->create([
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
    ]);

    $transition = $payment->getState()->getWorkflow()->getTransition('authorize');
    $payment->getState()->applyTransition($transition);

    $payment->setAuthorizedTime($this->time->getRequestTime())
      ->save();

    $klarnaOrder->acknowledge();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    $order = $payment->getOrder();
    $klarnaOrder = $this->connector->getOrder($order);

    /** @var \Drupal\commerce_klarna_payments\Event\RequestEvent $request */
    $request = $this->eventDispatcher
      ->dispatch(Events::CAPTURE_CREATE, new RequestEvent($order));

    try {
      $request->getRequest()
        ->setCapturedAmount((int) $amount->multiply('100')->getNumber());
      $capture = $klarnaOrder->createCapture($request->getRequest()->toArray());
      $capture->fetch();
    }
    catch (\Exception $e) {
      throw new PaymentGatewayException($e->getMessage(), $e->getCode(), $e);
    }

    $transition = $payment->getState()->getWorkflow()->getTransition('capture');
    $payment->getState()->applyTransition($transition);

    $payment->setAmount($amount);
    $payment->setRemoteId($capture->getId());
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);
    $order = $payment->getOrder();

    try {
      $klarnaOrder = $this->connector->getOrder($order);
      // @todo Find out, if a DELETE /payments/v1/authorizations/{authorizationToken}
      // request would be better here.
      $klarnaOrder->cancel();
    }
    catch (\Exception $e) {
      throw new PaymentGatewayException($e->getMessage(), $e->getCode(), $e);
    }

    $payment->setState('authorization_voided');
    $payment->save();
  }

}
