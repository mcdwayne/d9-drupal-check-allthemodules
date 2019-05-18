<?php

namespace Drupal\commerce_itransact\Plugin\Commerce\PaymentGateway;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the iTransact payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "itransact",
 *   label = @Translation("iTransact"),
 *   display_label = @Translation("iTransact"),
 *   payment_method_types = {"credit_card"},
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_itransact\PluginForm\Onsite\PaymentMethodAddForm",
 *     "edit-payment-method" = "Drupal\commerce_payment\PluginForm\PaymentMethodEditForm",
 *   },
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "mastercard", "visa",
 *   },
 * )
 */
class iTransact extends OnsitePaymentGatewayBase implements iTransactInterface {

  const LIVE_URL = 'https://secure.itransact.com/cgi-bin/rc/ord.cgi';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The ID of the parent config entity.
   *
   * Not available while the plugin is being configured.
   *
   * @var string
   */
  protected $entityId;

  /**
   * The payment type used by the gateway.
   *
   * @var \Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeInterface
   */
  protected $paymentType;

  /**
   * The payment method types handled by the gateway.
   *
   * @var \Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeInterface[]
   */
  protected $paymentMethodTypes;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;
 
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, EntityFieldManagerInterface $entity_field_manager, ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->entityFieldManager = $entity_field_manager;
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('commerce_itransact');
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
      $container->get('entity_field.manager'),
      $container->get('http_client'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'uid' => '',
      'phone_field' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order Form Unique ID (UID)'),
      '#default_value' => $this->configuration['uid'],
      '#description' => $this->t('Order Form Unique ID (UID) obtained from: @url', ['@url' => 'https://gateway.itransact.com/h/cp/merchant_settings/edit_integration']),
      '#required' => TRUE,
    ];

    $options = [];

    // Get all the fields from the customer profile.
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('profile', 'customer');
    foreach ($field_definitions as $field_definition) {
      if ($field_definition->getType() === 'telephone') {
        $options[$field_definition->id()] = $field_definition->label();
      }
    }

    $form['phone_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Telephone field'),
      '#default_value' => $this->configuration['phone_field'],
      '#description' => $this->t('Choose the profile field which should be used as the source for the telephone number.'),
      '#required' => TRUE,
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['uid'] = $values['uid'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {

  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {

  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {

  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {

  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {

  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {

  }

}
