<?php

namespace Drupal\commerce_ecpay\Plugin\Commerce\PaymentGateway;


use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the ECPay AIO Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "ecpay_aio_checkout_payment_gateway",
 *   label = @Translation("ECPay AIO checkout"),
 *   display_label = @Translation("ECPay"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_ecpay\PluginForm\AIOCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa", "jcb"
 *   },
 * )
 */
class AIOCheckoutPaymentGateway extends OffsitePaymentGatewayBase implements AIOCheckoutPaymentGatewayInterface {

  const REPLACE_MAPPING = [
    '%2d' => '_',
    '%5f' => '_',
    '%2e' => '.',
    '%21' => '!',
    '%2a' => '*',
    '%28' => '(',
    '%29' => ')',
  ];

  /** @var  \GuzzleHttp\ClientInterface $http_client */
  protected $http_client;

  /** @var  \Drupal\commerce_price\RounderInterface */
  protected $rounder;

  /** @var  \Psr\Log\LoggerInterface $logger */
  protected $logger;

  /**
   * Construct ECPay AIO checkout payment gateway
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   * @param \Drupal\Component\Datetime\TimeInterface $time
   * @param \GuzzleHttp\ClientInterface $http_client
   * @param \Drupal\commerce_price\RounderInterface $rounder
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, ClientInterface $http_client, RounderInterface $rounder, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->http_client = $http_client;
    $this->rounder = $rounder;
    $this->logger = $logger_factory->get('commerce_ecpay');
  }

  /**
   * @inheritDoc
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
      $container->get('http_client'),
      $container->get('commerce_price.rounder'),
      $container->get('logger.factory')
    );
  }

  /**
   * @inheritDoc
   */
  public function defaultConfiguration() {
    return [
      'merchant_id' => '',
      'hash_key' => '',
      'hash_iv' => '',
      'allowed_payment_methods' => '',
      'allowed_remember_credit_card' => 0,
      'trade_desc' => 'Purchase successfully!'
    ] + parent::defaultConfiguration();
  }

  /**
   * @inheritDoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];
    $form['hash_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hash key'),
      '#default_value' => $this->configuration['hash_key'],
      '#required' => TRUE,
    ];
    $form['hash_iv'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hash IV'),
      '#default_value' => $this->configuration['hash_iv'],
      '#required' => TRUE,
    ];
    $form['allowed_payment_methods'] = [
      '#type' => 'select',
      '#title' => $this->t('Allowed payment methods'),
      '#options' => [
        'Credit' => $this->t('Credit card'),
        'AndroidPay' => $this->t('Android Pay'),
        'WebATM' => $this->t('Web ATM'),
        'ATM' => $this->t('ATM'),
      ],
      '#multiple' => TRUE,
      '#default_value' => empty($this->configuration['allowed_payment_methods']) ? ['Credit'] : $this->configuration['allowed_payment_methods'],
      '#required' => TRUE,
    ];

    $allowed_payment_methods_parents = array_merge($form['#parents'], ['allowed_payment_methods']);
    $allowed_payment_methods_path = array_shift($allowed_payment_methods_parents);
    $allowed_payment_methods_path .= '[' . implode('][', $allowed_payment_methods_parents) . ']';

    $form['allowed_remember_credit_card'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allowed remember customer credit card'),
      '#states' => [
        'visible' => [
          ':input[name="' . $allowed_payment_methods_path . '"]' => [
            'value' => 'Credit',
          ],
        ],
      ],
    ];
    $form['trade_desc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description for the trade'),
      '#default_value' => $this->configuration['trade_desc'],
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!$form_state->getErrors() && $form_state->isSubmitted()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['hash_key'] = $values['hash_key'];
      $this->configuration['hash_iv'] = $values['hash_iv'];
      $this->configuration['allowed_payment_methods'] = $values['allowed_payment_methods'];
      $this->configuration['trade_desc'] = $values['trade_desc'];
      $this->configuration['mode'] = $values['mode'];
    }
  }

  /**
   * @inheritDoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state); // TODO: Change the autogenerated stub
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['hash_key'] = $values['hash_key'];
      $this->configuration['hash_iv'] = $values['hash_iv'];
      $this->configuration['allowed_payment_methods'] = $values['allowed_payment_methods'];
      $this->configuration['trade_desc'] = $values['trade_desc'];
      $this->configuration['mode'] = $values['mode'];
    }
  }


  /**
   * Get the API URL.
   * @param $uri
   * @return string The API URL
   * The API URL
   */
  public function getPaymentUrl($uri = '') {
    if ($this->getMode() != 'test') {
      $based_url = 'https://payment.ecpay.com.tw';
    } else {
      $based_url = 'https://payment-stage.ecpay.com.tw';
    }

    return $based_url . $uri;
  }

  /**
   * @inheritDoc
   */
  public function getVendorUrl($uri = '') {
    if ($this->getMode() != 'test') {
      $based_url = 'https://vendor.ecpay.com.tw';
    } else {
      $based_url = 'https://vendor-stage.ecpay.com.tw';
    }

    return $based_url . $uri;
  }

  /**
   * Performs a ECPay AIO payment checkout action
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *    The payment
   * @param array $extra
   *    Extra parameters needed for this request
   * @return array ECPay response
   * ECPay response
   *
   * @throws \Exception
   */
  public function checkout(PaymentInterface $payment, array $extra) {
    // Check if the currency is TWD or not
    if ($payment->getAmount()->getCurrencyCode() != 'TWD') {
      throw new PaymentGatewayException("The currency is not correct.");
    }

    $merchant_trade_no = $this->getMerchantTradeNo($payment);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();
    $order_items = $order->getItems();
    $item_names = [];

    foreach ($order_items as $item) {
      $title = $item->getTitle();
      $quantity = $item->getQuantity();

      $item_names[] = sprintf("%s *%s", $title, $quantity);
    }

    $configuration = $this->getConfiguration();
    /**
     * The order_id exists in CustomField1.
     * Once the payment is completed, ECPay will return/notify CustomField1
     * to Commerce module.
     * @see \Drupal\commerce_ecpay\Plugin\Commerce\PaymentGateway\AIOCheckoutPaymentGateway::onNotify()
     */
    $params = [
      'MerchantID' => $configuration['merchant_id'],
      'MerchantTradeNo' => $merchant_trade_no,
      'MerchantTradeDate' => date('Y/m/d H:i:s', $this->time->getCurrentTime()),
      'PaymentType' => 'aio',
      'TotalAmount' => round(floatval($payment->getAmount()->getNumber())),
      'TradeDesc' => empty($configuration['trade_desc']) ? 'Purchase successfully' : $configuration['trade_desc'],
      'ItemName' => substr(implode(', ', $item_names), 0, 200),
      'ReturnURL' => $this->getNotifyUrl()->toString(),
      'OrderResultURL' => $extra['return_url'],
      'ChoosePayment' => 'Credit',
      'EncryptType' => 1,
    ];

    $mac_value = $this->generateCheckMacValue($params);

    $params['CheckMacValue'] = $mac_value;

    $payment->setState('new');
    $payment->setRemoteId($merchant_trade_no);
    $payment->setRemoteState('');
    $payment->save();

    return $params;
  }

  /**
   * @inheritDoc
   */
  public function onNotify(Request $request) {
    if ($request->getMethod() != Request::METHOD_POST) {
      return FALSE;
    }

    $raw_result = $request->request->all();
    // Filter the return data
    $result = filter_var_array($raw_result, [
        'MerchantID' => FILTER_SANITIZE_STRING,
        'MerchantTradeNo' => FILTER_SANITIZE_STRING,
        'StoreID' => FILTER_SANITIZE_STRING,
        'RtnCode' => FILTER_SANITIZE_NUMBER_INT,
        'RtnMsg' => FILTER_SANITIZE_STRING,
        'TradeNo' => FILTER_SANITIZE_STRING,
        'TradeAmt' => FILTER_SANITIZE_NUMBER_INT,
        'PaymentDate' => FILTER_SANITIZE_STRING,
        'PaymentType' => FILTER_SANITIZE_STRING,
        'PaymentTypeChargeFee' => FILTER_SANITIZE_NUMBER_INT,
        'TradeDate' => FILTER_SANITIZE_STRING,
        'SimulatePaid' => FILTER_SANITIZE_NUMBER_INT,
        'CustomField1' => FILTER_SANITIZE_STRING,
        'CustomField2' => FILTER_SANITIZE_STRING,
        'CustomField3' => FILTER_SANITIZE_STRING,
        'CustomField4' => FILTER_SANITIZE_STRING,
        'CheckMacValue' => FILTER_SANITIZE_STRING,
      ], FALSE);

    $mac_value = $this->generateCheckMacValue($raw_result);
    // Make sure the CheckMacValue is same
    if ($mac_value != $result['CheckMacValue']) {
      $this->logger->warning("The MAC value is incorrect.");
      return FALSE;
    }

    // Check if the RtnCode is 1
    if (!$result['RtnCode']) {
      $this->logger->error('The @merchant_trade_no occurs error. @rtn_code : @rtn_msg', [
        '@merchant_trade_no' => $result['MerchantTradeNo'],
        '@rtn_code' => $result['RtnCode'],
        '@rtn_msg' => $result['RtnMsg'],
      ]);
      return FALSE;
    }

    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payments = $payment_storage->loadByProperties([
      'remote_id' => $result['MerchantTradeNo'],
    ]);
    $payment = reset($payments);

    if (!isset($payment)) {
      $this->logger->error('The MerchantTradeNo #' . $result['MerchantTradeNo'] .  ' doesn\'t exist');
      return FALSE;
    }

    $payment->setState('completed');
    $payment->setRemoteState($result['RtnCode']);
    $payment->save();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();
    $order->setData('ecpay_aio_checkout_payment_gateway', $result['MerchantTradeNo']);
    // Apply the order's transition and check step
    $order->set('checkout_step', 'complete');
    $transition = $order->getState()->getWorkflow()->getTransition('place');
    $order->getState()->applyTransition($transition);
    $order->save();

    return new Response('1|OK', 200, [
      'Content-Type' => 'text/plain',
    ]);
  }

  /**
   * @inheritDoc
   */
  public function onReturn(OrderInterface $order, Request $request) {
    parent::onReturn($order, $request);

    $merchant_trade_no = $order->getData('ecpay_aio_checkout_payment_gateway');

    if (empty($merchant_trade_no)) {
      throw new PaymentGatewayException('Missing merchant trade no in ECPay');
    }

    // Check the payment from ECPay
    $query_trade_url = $this->getPaymentUrl('/Cashier/QueryTradeInfo/V5');
    $configuration = $this->getConfiguration();
    $params = [
      'MerchantID' => $configuration['merchant_id'],
      'MerchantTradeNo' => $merchant_trade_no,
      'TimeStamp' => $this->time->getCurrentTime(),
    ];

    $params_mac_value = $this->generateCheckMacValue($params);

    $params['CheckMacValue'] = $params_mac_value;

    $response = $this->http_client->request('POST', $query_trade_url, [
      'form_params' => $params
    ]);
    $content = $response->getBody()->getContents();
    $content = str_replace(' ', '%20', $content);
    $content = str_replace('+', '%2B', $content);
    parse_str($content, $raw_result);

    $mac_value = $this->generateCheckMacValue($raw_result);
    $result = filter_var_array($raw_result, [
      'MerchantID' => FILTER_SANITIZE_STRING,
      'MerchantTradeNo' => FILTER_SANITIZE_STRING,
      'StoreID' => FILTER_SANITIZE_STRING,
      'TradeNo' => FILTER_SANITIZE_STRING,
      'TradeAmt' => FILTER_SANITIZE_NUMBER_INT,
      'PaymentDate' => FILTER_SANITIZE_STRING,
      'PaymentType' => FILTER_SANITIZE_STRING,
      'HandlingCharge' => FILTER_SANITIZE_NUMBER_INT,
      'PaymentTypeChargeFee' => FILTER_SANITIZE_NUMBER_INT,
      'TradeDate' => FILTER_SANITIZE_STRING,
      'TradeStatus' => FILTER_SANITIZE_STRING,
      'ItemName' => FILTER_SANITIZE_STRING,
      'CustomField1' => FILTER_SANITIZE_STRING,
      'CustomField2' => FILTER_SANITIZE_STRING,
      'CustomField3' => FILTER_SANITIZE_STRING,
      'CustomField4' => FILTER_SANITIZE_STRING,
      'CheckMacValue' => FILTER_SANITIZE_STRING,
    ], FALSE);


    if (!isset($result['CheckMacValue'])) {
      throw new PaymentGatewayException("onReturn: Missing MAC value");
    }

    if ($mac_value != $result['CheckMacValue']) {
      throw new PaymentGatewayException("onReturn: The MAC value is incorrect");
    }

    if ($result['TradeStatus'] == '0') {
      throw new PaymentGatewayException("onReturn: The order doesn't complete checkout");
    }
  }

  /**
   * Captures the give authorized payment.
   *
   * Only payments in the 'authorization' state can be captured.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to capture.
   * @param \Drupal\commerce_price\Price $amount
   *   The amount to capture. If NULL, defaults to the entire payment amount.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    // TODO: Implement capturePayment() method.
    // ECPay doesn't support to capture payment in credit card method
  }

  /**
   * Refunds the given payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to refund.
   * @param \Drupal\commerce_price\Price $amount
   *   The amount to refund. If NULL, defaults to the entire payment amount.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    // TODO: Implement refundPayment() method.
  }

  /**
   * Voids the given payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to void.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function voidPayment(PaymentInterface $payment) {
    // TODO: Implement voidPayment() method.
  }

  /**
   * Get unique MerchantTradeNo
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   * @return string
   */
  protected function getMerchantTradeNo(PaymentInterface $payment) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    return strval($order->id()) . strval($this->time->getCurrentTime());
  }

  /**
   * @param array $params
   *    Params payload
   *
   * @return string|null
   *    MAC value
   */
  protected function generateCheckMacValue(array $params = []) {
    if (isset($params)) {
      unset($params['CheckMacValue']);
      uksort($params, 'strcasecmp');

      $configuration = $this->getConfiguration();
      $hash_key_param = [
        'HashKey' => $configuration['hash_key'],
      ];
      $hash_iv_param = [
        'HashIV' => $configuration['hash_iv'],
      ];
      $buildArray = array_merge($hash_key_param, $params, $hash_iv_param);
      $checkedParams = urldecode(http_build_query($buildArray));
      $checkedParams = urlencode($checkedParams);
      $checkedParams = strtolower($checkedParams);

      foreach (static::REPLACE_MAPPING as $search => $replace) {
        $checkedParams = str_replace($search, $replace, $checkedParams);
      }

      $checkedParams = hash('sha256', $checkedParams);
      $checkedParams = strtoupper($checkedParams);

      return $checkedParams;
    }
  }

}
