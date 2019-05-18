<?php

namespace Drupal\commerce_datatrans\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_datatrans\DatatransHelper;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the Datatrans payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "datatrans",
 *   label = "Datatrans",
 *   display_label = "Datatrans",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_datatrans\PluginForm\DatatransForm",
 *   },
 *   payment_method_types = {"credit_card", "datatrans_alias"},
 *   credit_card_types = {
 *     "VIS", "ECA", "AMX", "BPY", "DIN", "DIS", "DEA", "DIB", "DII", "DNK",
 *     "DVI", "ELV", "ESY", "JCB", "JEL", "MAU", "MDP", "MFA", "MFG", "MFX",
 *     "MMS", "MNB", "MYO", "PAP", "PEF", "PFC", "PSC", "PYL", "PYO", "REK",
 *     "SWB", "TWI", "MPW", "ACC", "INT", "PPA", "GPA", "GEP"
 *   },
 * )
 */
class Datatrans extends OffsitePaymentGatewayBase {

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a Datatrans object.
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->logger = $logger_factory->get('commerce_datatrans');
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
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'merchant_id' => '',
        'service_url' => ' https://pilot.datatrans.biz/upp/jsp/upStart.jsp',
        'req_type' => 'CAA',
        'use_alias' => FALSE,
        'security_level' => 2,
        'sign' => '',
        'hmac_key' => '',
        'use_hmac_2' => FALSE,
        'hmac_key_2' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => t('Merchant-ID'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];

    $form['service_url'] = [
      '#type' => 'textfield',
      '#title' => t('Service URL'),
      '#default_value' => $this->configuration['service_url'],
      '#required' => TRUE,
    ];

    $form['req_type'] = [
      '#type' => 'select',
      '#title' => t('Request Type'),
      '#options' => [
        'NOA' => t('Authorization only'),
        'CAA' => t('Authorization with immediate settlement'),
        'ignore' => t('According to the setting in the Web Admin Tool'),
      ],
      '#default_value' => $this->configuration['req_type'],
    ];

    $form['use_alias'] = [
      '#type' => 'checkbox',
      '#title' => 'Use Alias',
      '#default_value' => $this->configuration['use_alias'],
      '#description' => t('Enable this option to always request an alias from datatrans. This is used for recurring payments and should be disabled if not necessary. If the response does not provide an alias, the payment will not be settled (or refunded, in case it was settled immediately) and the payment needs to be repeated.'),
    ];

    $url = Url::fromUri('https://pilot.datatrans.biz/showcase/doc/Technical_Implementation_Guide.pdf', ['external' => TRUE])->toString();
    $form['security'] = [
      '#type' => 'fieldset',
      '#title' => t('Security Settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#description' => t('You should not work with anything else than security level 2 on a productive system. Without the HMAC key there is no way to check whether the data really comes from Datatrans. You can find more details about the security levels in your Datatrans account at UPP ADMINISTRATION -> Security. Or check the technical information in the <a href=":url">Technical_Implementation_Guide</a>', [':url' => $url]),
    ];

    $form['security']['security_level'] = [
      '#type' => 'select',
      '#title' => t('Security Level'),
      '#options' => [
        '0' => t('Level 0. No additional security element will be send with payment messages. (not recommended)'),
        '1' => t('Level 1. An additional Merchant-Identification will be send with payment messages'),
        '2' => t('Level 2. Important parameters will be digitally signed (HMAC-SHA256) and sent with payment messages'),
      ],
      '#default_value' => $this->configuration['security_level'],
    ];

    $form['security']['sign'] = [
      '#type' => 'textfield',
      '#title' => t('Merchant control sign'),
      '#default_value' => $this->configuration['sign'],
      '#description' => t('Used for security level 1'),
      '#states' => [
        'visible' => [
          ':input[name="configuration[security][security_level]"]' => ['value' => '1'],
        ],
      ],
    ];

    $form['security']['hmac_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HMAC Key'),
      '#default_value' => $this->configuration['hmac_key'],
      '#description' => t('Used for security level 2'),
      '#states' => [
        'visible' => [
          ':input[name="configuration[security][security_level]"]' => ['value' => '2'],
        ],
      ],
    ];

    $form['security']['use_hmac_2'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use HMAC 2'),
      '#default_value' => $this->configuration['use_hmac_2'],
      '#states' => array(
        'visible' => array(
          ':input[name="configuration[security][security_level]"]' => ['value' => '2'],
        ),
      ),
    );

    $form['security']['hmac_key_2'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('HMAC Key 2'),
      '#default_value' => $this->configuration['hmac_key_2'],
      '#states' => array(
        'visible' => array(
          ':input[name="configuration[security][security_level]"]' => ['value' => '2'],
        ),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['service_url'] = $values['service_url'];
      $this->configuration['req_type'] = $values['req_type'];
      $this->configuration['use_alias'] = $values['use_alias'];
      $this->configuration['security_level'] = $values['security']['security_level'];
      $this->configuration['sign'] = $values['security']['sign'];
      $this->configuration['hmac_key'] = $values['security']['hmac_key'];
      $this->configuration['use_hmac_2'] = $values['security']['use_hmac_2'];
      $this->configuration['hmac_key_2'] = $values['security']['hmac_key_2'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // @todo Add examples of request validation.
    $post_data = $request->request->all();

    if (!$this->validateResponseData($post_data, $order)) {
      drupal_set_message($this->t('There was a problem while processing your payment.'), 'warning');
      throw new PaymentGatewayException();
    }

    $this->processPayment($post_data, $order);
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $post_data = $request->request->all();

    /** @var \Drupal\commerce_order\entity\OrderInterface $order */
    $order = $this->entityTypeManager->getStorage('commerce_order')->load($post_data['refno']);
    if (!$order) {
      return new Response('', 400);
    }

    if ($this->validateResponseData($post_data, $order)) {
      $this->processPayment($post_data, $order);
    }
    else {
      return new Response('', 400);
    }
  }

  /**
   * Validate the data received from Datatrans.
   *
   * @param array $post_data
   *   Data received from Datatrans.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order entity.
   *
   * @return bool
   *   The validation result.
   */
  protected function validateResponseData(array $post_data, OrderInterface $order) {
    $gateway_config = $this->getConfiguration();

    // We must have post data, order id and this order must exist.
    if (empty($post_data) || empty($post_data['refno'])) {
      return FALSE;
    }

    // Error and cancel.
    if ($post_data['status'] == 'error') {
      $this->logger->error('The payment gateway returned the error code %code (%code_text) with details %details for order %order_id', [
        '%code' => $post_data['errorCode'],
        '%code_text' => DatatransHelper::mapErrorCode($post_data['errorCode']),
        '%details' => $post_data['errorDetail'],
        '%order_id' => $order->id(),
      ]);
      return FALSE;
    }

    if ($post_data['status'] == 'cancel') {
      $this->logger->info('The user canceled the authorisation process for order %order_id', [
        '%order_id' => $order->id(),
      ]);
      return FALSE;
    }

    // Security levels.
    // @todo Does this really need to be submitted/verified?
    if (empty($post_data['security_level']) || $post_data['security_level'] != $gateway_config['security_level']) {
      return FALSE;
    }

    // If security level 2 is configured then generate and use a sign.
    if ($gateway_config['security_level'] == 2) {
      // If a second hmac key is configured then use that to sign.
      $key = $gateway_config['use_hmac_2'] ? $gateway_config['hmac_key_2'] : $gateway_config['hmac_key'];
      $sign2 = DatatransHelper::generateSign($key, $gateway_config['merchant_id'], $post_data['amount'], $post_data['currency'], $post_data['uppTransactionId']);

      // Check for correct sign.
      if (empty($post_data['sign2']) || $sign2 != $post_data['sign2']) {
        $this->logger->warning('Detected non matching signs while processing order %order_id.', [
          '%order_id' => $order->id(),
        ]);
        return FALSE;
      }
    }

    if ($post_data['status'] == 'success') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Process the payment.
   *
   * @param array $post_data
   *   Array with data received from Datatrans.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|bool
   *   The payment entity or boolean false if the payment with
   *   this authorisation code was already processed.
   */
  protected function processPayment(array $post_data, OrderInterface $order) {
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    if ($payment_storage->loadByProperties(['remote_id' => $post_data['authorizationCode']])) {
      return FALSE;
    }

    $payment = $payment_storage->create([
      'state' => 'authorization',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
      'remote_id' => $post_data['uppTransactionId'],
      'remote_state' => $post_data['responseMessage'],
      'authorized' => $this->time->getRequestTime(),
    ]);
    $payment->save();

    // Create a payment method if we use alias.
    if (isset($post_data['useAlias']) && $post_data['useAlias'] === 'true') {
      $payment_method = $this->createPaymentMethod($post_data);
      $order->set('payment_method', $payment_method);
      $order->save();
    }

    return $payment;
  }


  /**
   * Create an alias payment method.
   *
   * @todo https://www.drupal.org/node/2838380
   *
   * @param array $payment_details
   *   Array of payment details we get from Datatrans.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface
   *   The created payment method.
   */
  public function createPaymentMethod(array $payment_details) {
    $payment_method = PaymentMethod::create([
      'payment_gateway' => $this->pluginId,
      'type' => 'datatrans_alias',
      'reusable' => TRUE,
      'pmethod' => $payment_details['pmethod'],
      'masked_cc' => $payment_details['maskedCC'],
      'expm' => $payment_details['expm'],
      'expy' => $payment_details['expy'],
    ]);

    $expires = CreditCard::calculateExpirationTimestamp($payment_details['expm'], $payment_details['expy']);
    $payment_method->setRemoteId($payment_details['aliasCC']);
    $payment_method->setExpiresTime($expires);
    $payment_method->save();
    return $payment_method;
  }

  /**
   * Delete an alias payment method.
   *
   * @todo https://www.drupal.org/node/2838380
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // Delete the remote record here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    // Delete the local entity.
    $payment_method->delete();
  }
}
