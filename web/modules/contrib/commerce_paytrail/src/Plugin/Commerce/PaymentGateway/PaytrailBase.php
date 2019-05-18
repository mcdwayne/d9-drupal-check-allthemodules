<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\commerce_paytrail\Entity\PaymentMethod;
use Drupal\commerce_paytrail\Exception\InvalidValueException;
use Drupal\commerce_paytrail\Exception\SecurityHashMismatchException;
use Drupal\commerce_paytrail\PaymentManagerInterface;
use Drupal\commerce_paytrail\Repository\Response as PaytrailResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Messenger\MessengerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the Paytrail payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paytrail",
 *   label = "Paytrail",
 *   display_label = "Paytrail",
 *   payment_method_types = {"paytrail"},
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_paytrail\PluginForm\OffsiteRedirect\PaytrailOffsiteForm",
 *   },
 * )
 */
class PaytrailBase extends OffsitePaymentGatewayBase implements SupportsNotificationsInterface {

  use MessengerTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The payment manager.
   *
   * @var \Drupal\commerce_paytrail\PaymentManagerInterface
   */
  protected $paymentManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The paytrail host.
   *
   * @var string
   */
  const HOST = 'https://payment.paytrail.com/e2';

  /**
   * The default merchant id used for testing.
   *
   * @var string
   */
  const MERCHANT_ID = '13466';

  /**
   * The default merchant hash used for testing.
   *
   * @var string
   */
  const MERCHANT_HASH = '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ';

  /**
   * The payer details.
   *
   * @var string
   */
  const PAYER_DETAILS = 'payer';

  /**
   * The product details.
   *
   * @var string
   */
  const PRODUCT_DETAILS = 'product';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\commerce_paytrail\Plugin\Commerce\PaymentGateway\PaytrailBase $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    // Populate via setters to avoid overriding the parent constructor.
    $instance->setLanguageManager($container->get('language_manager'))
      ->setPaymentManager($container->get('commerce_paytrail.payment_manager'))
      ->setLogger($container->get('logger.channel.commerce_paytrail'));

    return $instance;
  }

  /**
   * Setter to populate language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   *
   * @return $this
   *   The self.
   */
  public function setLanguageManager(LanguageManagerInterface $languageManager) : self {
    $this->languageManager = $languageManager;
    return $this;
  }

  /**
   * Gets the payment manager.
   *
   * @param \Drupal\commerce_paytrail\PaymentManagerInterface $paymentManager
   *   The payment manager.
   *
   * @return $this
   *   The self.
   */
  public function setPaymentManager(PaymentManagerInterface $paymentManager) : self {
    $this->paymentManager = $paymentManager;
    return $this;
  }

  /**
   * Sets the logger.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   *
   * @return $this
   *   The self.
   */
  public function setLogger(LoggerInterface $logger) : self {
    $this->logger = $logger;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() : array {
    return [
      'culture' => 'automatic',
      'merchant_id' => static::MERCHANT_ID,
      'merchant_hash' => static::MERCHANT_HASH,
      'allow_ipn_create_payment' => FALSE,
      'included_data' => [
        static::PAYER_DETAILS => static::PAYER_DETAILS,
        static::PRODUCT_DETAILS => static::PRODUCT_DETAILS,
      ],
      'bypass_mode' => FALSE,
    ] + parent::defaultConfiguration();
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
   * Whether to allow ipn to create new payments.
   *
   * This is used to mitigate the issue when user never returns from
   * the payment gateway (to complete the order), but has paid the order.
   *
   * @return bool
   *   TRUE if allowed, FALSE if not.
   */
  public function ipnAllowedToCreatePayment() : bool {
    return (bool) $this->configuration['allow_ipn_create_payment'];
  }

  /**
   * Gets the merchant id.
   *
   * @return mixed|string
   *   The merchant id.
   */
  public function getMerchantId() : string {
    return $this->getMode() == 'test' ? static::MERCHANT_ID : $this->configuration['merchant_id'];
  }

  /**
   * Gets the merchant hash.
   *
   * @return mixed|string
   *   The merchant hash.
   */
  public function getMerchantHash() : string {
    return $this->getMode() == 'test' ? static::MERCHANT_HASH : $this->configuration['merchant_hash'];
  }

  /**
   * Allow plugin forms to log messages.
   *
   * @todo Should they just use \Drupal?
   *
   * @param string $message
   *   The message to log.
   * @param int $severity
   *   The severity.
   */
  public function log($message, $severity = RfcLogLevel::CRITICAL) : void {
    $this->logger->log($severity, $message);
  }

  /**
   * Gets the visible payment methods.
   *
   * @param bool $enabled
   *   Whether to only load enabled payment methods.
   *
   * @return \Drupal\commerce_paytrail\Entity\PaymentMethod[]
   *   The payment methods.
   */
  public function getVisibleMethods($enabled = TRUE) : array {
    $storage = $this->entityTypeManager->getStorage('paytrail_payment_method');

    if (!$enabled) {
      return $storage->loadMultiple();
    }
    return $storage->loadByProperties(['status' => TRUE]);
  }

  /**
   * Gets the payment manager.
   *
   * @return \Drupal\commerce_paytrail\PaymentManagerInterface
   *   The payment manager.
   */
  public function getPaymentManager() : PaymentManagerInterface {
    return $this->paymentManager;
  }

  /**
   * Get used langcode.
   */
  public function getCulture() : string {
    // Attempt to autodetect.
    if ($this->configuration['culture'] === 'automatic') {
      $mapping = [
        'fi' => 'fi_FI',
        'sv' => 'sv_SE',
        'en' => 'en_US',
      ];
      $langcode = $this->languageManager->getCurrentLanguage()->getId();

      return isset($mapping[$langcode]) ? $mapping[$langcode] : 'en_US';
    }
    return $this->configuration['culture'];
  }

  /**
   * Check if given data type should be delivered to Paytrail.
   *
   * @param string $type
   *   The type.
   *
   * @return bool
   *   TRUE if data is included, FALSE if not.
   */
  public function isDataIncluded(string $type) : bool {
    if (isset($this->configuration['included_data'][$type])) {
      return $this->configuration['included_data'][$type] === $type;
    }
    return FALSE;
  }

  /**
   * Checks if the bypass mode is enabled.
   *
   * @return bool
   *   TRUE if enabled, FALSE if not.
   */
  public function isBypassModeEnabled() : bool {
    return $this->configuration['bypass_mode'] ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#description' => $this->t('Merchant ID provided by PaytrailBase.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['merchant_id'],
    ];

    $form['merchant_hash'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Authentication Hash'),
      '#description' => $this->t('Authentication Hash code calculated using MD5.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['merchant_hash'],
    ];

    $form['included_data'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Data to deliver'),
      '#default_value' => $this->configuration['included_data'],
      '#options' => [
        static::PRODUCT_DETAILS => $this->t('Product details'),
        static::PAYER_DETAILS => $this->t('Payer details'),
      ],
    ];

    $form['culture'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#description' => $this->t('Affects on default language and how amounts are shown on payment method selection page.'),
      '#options' => [
        'automatic' => $this->t('Automatic'),
        'fi_FI' => $this->t('Finnish'),
        'sv_SE' => $this->t('Swedish'),
        'en_US' => $this->t('English'),
      ],
      '#default_value' => $this->configuration['culture'],
    ];

    $form['allow_ipn_create_payment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow IPN to create new payments'),
      '#description' => $this->t('Enable this to allow Paytrail to automatically create a new payment in case user never returns from the payment gateway.'),
      '#default_value' => $this->configuration['allow_ipn_create_payment'],
    ];

    $form['bypass_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Bypass Paytrail's payment method selection page"),
      '#description' => $this->t('User will be redirected directly to the selected payment service'),
      '#default_value' => $this->configuration['bypass_mode'],
    ];

    $form['visible_methods'] = [
      '#type' => 'checkboxes',
      '#multiple' => TRUE,
      '#title' => $this->t('Visible payment methods'),
      '#description' => $this->t('List of payment methods that are to be shown on the payment page. If left empty all available payment methods shown.'),
      '#options' => array_map(function (PaymentMethod $value) {
        return $value->adminLabel();
      }, $this->getVisibleMethods(FALSE)),
      '#default_value' => array_map(function (PaymentMethod $value) {
        return $value->id();
      }, $this->getVisibleMethods()),
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

      foreach ($this->getVisibleMethods(FALSE) as $method) {
        // Enable / disable payment method based on user selection.
        $method->setStatus(in_array($method->id(), $values['visible_methods']))->save();
      }

      $this->configuration = array_merge($this->configuration, [
        'merchant_id' => $values['merchant_id'],
        'allow_ipn_create_payment' => $values['allow_ipn_create_payment'],
        'merchant_hash'  => $values['merchant_hash'],
        'bypass_mode' => $values['bypass_mode'],
        'included_data' => $values['included_data'],
        'culture' => $values['culture'],
      ]);
    }
  }

  /**
   * IPN callback.
   *
   * IPN will be called after a succesful paytrail payment. Payment will be
   * marked as captured if validation succeeded.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The 200 response code if validation succeeded.
   */
  public function onNotify(Request $request) : Response {
    $order = $this->entityTypeManager
      ->getStorage('commerce_order')
      ->load($request->query->get('ORDER_NUMBER'));

    if (!$order instanceof OrderInterface) {
      $this->logger
        ->notice($this->t('Notify callback called for an invalid order @order [@values]', [
          '@order' => $request->query->get('ORDER_NUMBER'),
          '@values' => print_r($request->query->all(), TRUE),
        ]));

      throw new NotFoundHttpException();
    }

    try {
      $response = PaytrailResponse::createFromRequest($this->getMerchantHash(), $order, $request);
      $response->isValidResponse();
    }
    catch (InvalidValueException | \InvalidArgumentException $e) {
      $this->logger
        ->notice($this->t('Invalid return url @order [@values] @exception', [
          '@order' => $order->id(),
          '@values' => print_r($request->query->all(), TRUE),
          '@exception' => $e->getMessage(),
        ]));

      return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
    }
    catch (SecurityHashMismatchException $e) {
      $this->logger
        ->notice($this->t('Hash mismatch for @order [@values]', [
          '@order' => $order->id(),
          '@values' => print_r($request->query->all(), TRUE),
        ]));

      return new Response(sprintf('Hash mismatch (%s).', $e->getReason()), Response::HTTP_BAD_REQUEST);
    }

    // Mark payment as captured.
    try {
      $this->paymentManager->createPaymentForOrder('capture', $order, $this, $response);
    }
    catch (\InvalidArgumentException $e) {
      // Invalid payment state.
      $this->logger
        ->error($this->t('Invalid payment state for @order [@values]', [
          '@order' => $order->id(),
          '@values' => print_r($request->query->all(), TRUE),
        ]));

      return new Response('Invalid payment state.', Response::HTTP_BAD_REQUEST);
    }
    catch (PaymentGatewayException $e) {
      // Transaction id mismatch.
      $this->logger
        ->error($this->t('Transaction id mismatch for @order [@values]', [
          '@order' => $order->id(),
          '@values' => print_r($request->query->all(), TRUE),
        ]));

      return new Response('Transaction id mismatch.', Response::HTTP_BAD_REQUEST);
    }
    return new Response();
  }

  /**
   * Validate and store transaction for order.
   *
   * Payment will be initially stored as 'authorized' until
   * paytrail calls the notify IPN.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function onReturn(OrderInterface $order, Request $request) : void {
    try {
      $response = PaytrailResponse::createFromRequest($this->getMerchantHash(), $order, $request);
    }
    catch (InvalidValueException | \InvalidArgumentException $e) {
      $this->messenger()->addError($this->t('Invalid return url.'));

      $this->logger
        ->critical($this->t('Validation failed (@exception) @order [@values]', [
          '@order' => $order->id(),
          '@values' => print_r($request->query->all(), TRUE),
          '@exception' => $e->getMessage(),
        ]));
      throw new PaymentGatewayException();
    }

    try {
      $response->isValidResponse();
    }
    catch (SecurityHashMismatchException $e) {
      $this->messenger()->addError($this->t('Validation failed due to security hash mismatch (@reason).', [
        '@reason' => $e->getReason(),
      ]));

      $this->logger
        ->critical($this->t('Hash validation failed @order [@values] (@exception)', [
          '@order' => $order->id(),
          '@values' => print_r($request->query->all(), TRUE),
          '@exception' => $e->getMessage(),
        ]));
      throw new PaymentGatewayException();
    }

    // Mark payment as authorized. Paytrail will attempt to call notify IPN
    // which will mark payment as captured.
    $this->paymentManager->createPaymentForOrder('authorized', $order, $this, $response);

    $this->messenger()->addMessage($this->t('Payment was processed.'));
  }

}
