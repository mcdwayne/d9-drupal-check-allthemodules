<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Drupal\commerce_verifone\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_verifone\DependencyInjection\OrderLockerHelper;
use Drupal\commerce_verifone\DependencyInjection\PaymentHelper;
use Drupal\commerce_verifone\DependencyInjection\SummaryHelper;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Verifone\Core\DependencyInjection\Configuration\Backend\BackendConfigurationImpl;
use \Verifone\Core\DependencyInjection\Service\OrderImpl;
use Verifone\Core\DependencyInjection\Service\TransactionImpl;
use Verifone\Core\Executor\BackendServiceExecutor;
use Verifone\Core\Service\Backend\RefundPaymentService;
use \Verifone\Core\ServiceFactory;
use \Verifone\Core\Service\FrontendResponse\FrontendResponseServiceImpl;
use \Verifone\Core\DependencyInjection\Transporter\CoreResponse;
use \Verifone\Core\ExecutorContainer;
use \Verifone\Core\DependencyInjection\CoreResponse\PaymentResponseImpl;
use \Verifone\Core\Converter\Response\CoreResponseConverter;
use Drupal\commerce_price\Price;


/**
 * Provides the Verifone Payment off-site Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "verifone_payment",
 *   label = @Translation("Verifone Payment (off-site)"),
 *   display_label = @Translation("Verifone Payment"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_verifone\PluginForm\VerifoneOffsiteForm",
 *   },
 * )
 */
class VerifonePayment extends OffsitePaymentGatewayBase implements VerifonePaymentInterface
{

  /** BASKET ITEMS */
  const BASKET_ITEMS_NO_SEND = '0';
  const BASKET_ITEMS_SEND_FOR_ALL = '1';
  const BASKET_ITEMS_SEND_FOR_INVOICE = '2';

  const BASKET_LIMIT = 48; // Verifone can handle 50 product lines, 48 because also shipping and discount

  const TRANSACTION_ID_DELIMITER = ';';

  const RETRY_DELAY_IN_SECONDS = 2;
  const RETRY_MAX_ATTEMPTS = 5;

  /** @var PaymentHelper */
  protected $_paymentHelper;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    PaymentTypeManager $payment_type_manager,
    PaymentMethodTypeManager $payment_method_type_manager,
    TimeInterface $time,
    PaymentHelper $paymentHelper
  )
  {
    $this->_paymentHelper = $paymentHelper;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('commerce_verifone.payment_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return [
        'merchant_agreement_code' => '',
        'merchant_agreement_code_test' => 'demo-merchant-agreement',
        'key_handling_mode' => 0,
        'keys_directory' => '',
        'shop_private_keyfile' => '',
        'shop_private_keyfile_test' => '',
        'pay_page_public_keyfile' => 'verifone-e-commerce-live-public-key.pem',
        'pay_page_public_keyfile_test' => 'verifone-e-commerce-test-public-key.pem',
        'pay_page_url_1' => 'https://epayment1.point.fi/pw/payment',
        'pay_page_url_2' => 'https://epayment2.point.fi/pw/payment',
        'pay_page_url_3' => '',
        'delayed_url' => '',
        'payment_page_language' => 'fi_FI',
        'validate_url' => 1,
        'skip_confirmation_page' => 1,
        'style_code' => '',
        'basket_item_sending' => self::BASKET_ITEMS_NO_SEND,
        'allow_to_save_cc' => 0,
        'disable_rsa_blinding' => 0,
        'customer_external_id' => ''
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $form = parent::buildConfigurationForm($form, $form_state);

    $gatewayId = $form_state->getValue('id');
    $summaryData = new SummaryHelper($gatewayId, $this->getConfiguration(), $this->defaultConfiguration());

    $data = [
      'modalHeader' => $this->t('Configuration summary') . ' - ' . $gatewayId,
      'modalButton' => $this->t('Display configuration summary'),
      'configurationData' => $summaryData->getConfigurationDataForDisplay()
    ];

    $path = \Drupal::service('module_handler')->getModule('commerce_verifone')->getPath() . '/assets/templates/configuration_summary.twig';
    $form['summary'] = [
      '#type' => 'inline_template',
      '#template' => file_get_contents($path),
      '#context' => ['field' => $data],
      '#class' => 'depends-key_handling_mode-0'
    ];

    $form['merchant_agreement_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Verifone Payment production merchant agreement code'),
      '#description' => $this->t('Verifone Payment production merchant agreement code'),
      '#default_value' => $this->configuration['merchant_agreement_code'],
      '#required' => false,
    ];

    $form['merchant_agreement_code_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Verifone Payment test merchant agreement code'),
      '#description' => $this->t('Verifone Payment test merchant agreement code'),
      '#default_value' => $this->configuration['merchant_agreement_code_test'],
      '#required' => false,
    ];

    $form['key_handling_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment service key handling'),
      '#description' => '',
      '#default_value' => $this->configuration['key_handling_mode'],
      '#required' => false,
      '#options' => [
        0 => $this->t('Automatic (Simple)'),
        1 => $this->t('Manual (Advanced)')
      ]
    ];

    $path = \Drupal::service('module_handler')->getModule('commerce_verifone')->getPath() . '/assets/templates/configuration_generate_keys.twig';
    $context['href'] = Url::fromRoute('commerce_verifone.admin.generateKeys');

    $msg1 = $this->t('Are you sure you want to generate keys? The keys are stored in database.');
    $msg2 = $this->t('After creating a new key, remember to copy this key to payment operator configuration settings, otherwise the payment will be broken');
    $context['confirmMessage'] = sprintf($msg1 . "\n\n" . $msg2);

    $context['generateLiveLabel'] = $this->t('Generate live keys');
    $context['generateLiveDesc'] = $this->t('Uses preset keys by default, only needed if using custom test agreements');
    $context['generateTestLabel'] = $this->t('Generate test keys');
    $context['generateTestDesc'] = $this->t('When you generate live keys, you will need to upload the new public key to Verifone Payment portal');
    $context['class'] = 'depends-key_handling_mode-0';

    $form['generate_keys'] = [
      '#type' => 'inline_template',
      '#template' => file_get_contents($path),
      '#context' => ['field' => $context],
      '#class' => 'depends-key_handling_mode-0'
    ];

    $form['keys_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Directory for store keys'),
      '#description' => $this->t('A path to the directory for generated files'),
      '#default_value' => $this->configuration['keys_directory'],
      '#required' => false,
      '#attributes' => ['class' => ['depends-key_handling_mode-1']]
    ];

    $form['shop_private_keyfile'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shop private key filename') . sprintf(' (%s)', $this->t('Live')),
      '#description' => $this->t('Filename of shop secret key file generated with Verifone Payment key pair generator') . sprintf(' (%s)', $this->t('Live')),
      '#default_value' => $this->configuration['shop_private_keyfile'],
      '#required' => false,
      '#attributes' => ['class' => ['depends-key_handling_mode-1']]
    ];

    $form['shop_private_keyfile_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shop private key filename') . sprintf(' (%s)', $this->t('Test')),
      '#description' => $this->t('Filename of shop secret key file generated with Verifone Payment key pair generator') . sprintf(' (%s)', $this->t('Test')),
      '#default_value' => $this->configuration['shop_private_keyfile_test'],
      '#required' => false,
      '#attributes' => ['class' => ['depends-key_handling_mode-1']]
    ];

    $form['pay_page_url_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pay page URL 1'),
      '#description' => $this->t('Required. URL to the payment system'),
      '#default_value' => $this->configuration['pay_page_url_1'],
      '#required' => false,
    ];

    $form['pay_page_url_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pay page URL 2'),
      '#description' => $this->t('Optional. Second redundant URL to the payment system'),
      '#default_value' => $this->configuration['pay_page_url_2'],
      '#required' => false,
    ];

    $form['pay_page_url_3'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pay page URL 3'),
      '#description' => $this->t('Optional. Third redundant URL to the payment system'),
      '#default_value' => $this->configuration['pay_page_url_3'],
      '#required' => false,
    ];

    $form['payment_page_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment page language'),
      '#description' => $this->t('Select language which will be use on payment page'),
      '#default_value' => $this->configuration['payment_page_language'],
      '#required' => false,
      '#options' => [
        'fi_FI' => $this->t('Finnish'),
        'sv_SE' => $this->t('Swedish'),
        'no_NO' => $this->t('Norwegian'),
        'dk_DK' => $this->t('Danish'),
        'sv_FI' => $this->t('Swedish (Finland)'),
        'en_GB' => $this->t('English'),
      ]
    ];

    $form['validate_url'] = [
      '#type' => 'select',
      '#title' => $this->t('Check payment node availability'),
      '#description' => $this->t('Make a check that payment node is available'),
      '#default_value' => $this->configuration['validate_url'],
      '#required' => false,
      '#options' => $this->_yesNoOptions()
    ];

    $form['skip_confirmation_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Skip confirmation page'),
      '#description' => $this->t('Return directly to shop after payment'),
      '#default_value' => $this->configuration['skip_confirmation_page'],
      '#required' => false,
      '#options' => $this->_yesNoOptions()
    ];

    $form['style_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Style code'),
      '#description' => $this->t('Use of custom payment page template needs first to be uploaded and to be approved by Verifone Payment'),
      '#default_value' => $this->configuration['style_code'],
      '#required' => false
    ];

    $form['customer_external_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer identifier'),
      '#description' => $this->t('In case, when phone field is configured, then please set field name for example field_phone. When the field is empty, then the email address will be used.'),
      '#default_value' => $this->configuration['customer_external_id'],
      '#required' => false,
    ];

    $form['basket_item_sending'] = [
      '#type' => 'select',
      '#title' => $this->t('Basket Item Sending'),
      '#description' => $this->t('Select for which type of order should send items.'),
      '#default_value' => $this->configuration['basket_item_sending'],
      '#required' => false,
      '#options' => [
        self::BASKET_ITEMS_NO_SEND => $this->t('Do not send basket items'),
        self::BASKET_ITEMS_SEND_FOR_ALL => $this->t('Send for all payment methods')
      ]
    ];

//    $form['allow_to_save_cc'] = [
//      '#type' => 'select',
//      '#title' => $this->t('Allow to save Credit Cards'),
//      '#description' =>'',
//      '#default_value' => $this->configuration['allow_to_save_cc'],
//      '#required' => false,
//      '#options' => $this->_yesNoOptions()
//    ];

    $form['disable_rsa_blinding'] = [
      '#type' => 'select',
      '#title' => $this->t('Disable rsa blinding'),
      '#description' => $this->t('Define CRYPT_RSA_DISABLE_BLINDING as true in case of custom PHP build or PHP7 (experimental)'),
      '#default_value' => $this->configuration['disable_rsa_blinding'],
      '#required' => false,
      '#options' => $this->_yesNoOptions()
    ];

    $form['#attached']['library'][] = 'commerce_verifone/verifone_admin';

    return $form;
  }

  protected function _yesNoOptions()
  {
    return [
      1 => $this->t('Yes'),
      0 => $this->t('No'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['merchant_agreement_code'] = $values['merchant_agreement_code'];
      $this->configuration['merchant_agreement_code_test'] = $values['merchant_agreement_code_test'];
      $this->configuration['key_handling_mode'] = $values['key_handling_mode'];
      $this->configuration['keys_directory'] = $values['keys_directory'];
      $this->configuration['shop_private_keyfile'] = $values['shop_private_keyfile'];
      $this->configuration['shop_private_keyfile_test'] = $values['shop_private_keyfile_test'];
      $this->configuration['pay_page_public_keyfile'] = $values['pay_page_public_keyfile'];
      $this->configuration['pay_page_public_keyfile_test'] = $values['pay_page_public_keyfile_test'];
      $this->configuration['pay_page_url_1'] = $values['pay_page_url_1'];
      $this->configuration['pay_page_url_2'] = $values['pay_page_url_2'];
      $this->configuration['pay_page_url_3'] = $values['pay_page_url_3'];
      $this->configuration['payment_page_language'] = $values['payment_page_language'];
      $this->configuration['validate_url'] = $values['validate_url'];
      $this->configuration['skip_confirmation_page'] = $values['skip_confirmation_page'];
      $this->configuration['style_code'] = $values['style_code'];
      $this->configuration['basket_item_sending'] = $values['basket_item_sending'];
      $this->configuration['allow_to_save_cc'] = $values['allow_to_save_cc'];
      $this->configuration['disable_rsa_blinding'] = $values['disable_rsa_blinding'];
      $this->configuration['customer_external_id'] = $values['customer_external_id'];
    }
  }

  public function onReturn(OrderInterface $order, Request $request)
  {

    $orderLocker = new OrderLockerHelper();

    $params = $request->request->all();

    /** @var FrontendResponseServiceImpl $service */
    $service = ServiceFactory::createResponseService($params);

    $orderNumber = $service->getOrderNumber();

    if ($orderNumber !== (string)$order->id()) {
      throw new PaymentGatewayException('Payment failed - wrong order id');
    }

    if ($order->getState()->getValue()['value'] === 'completed') {
      // Don't need to process paid order.
      // Order changed to paid by delayed functionality

      if ($orderLocker->isLockedOrder($orderNumber)) {
        $orderLocker->unlockOrder($orderNumber);
      }

      return parent::onReturn($order, $request);
    }

    $attempts = 0;
    while (!$orderLocker->lockOrder($orderNumber) && $attempts < self::RETRY_MAX_ATTEMPTS) {
      sleep(self::RETRY_DELAY_IN_SECONDS);
      ++$attempts;
    }

    if ($attempts > 0 && $attempts < self::RETRY_MAX_ATTEMPTS) {

      $orderTmp = \Drupal\commerce_order\Entity\Order::load($order->id());

      if ($orderTmp && $orderTmp->getState()->getValue()['value'] === 'completed') {
        // Don't need to process paid order.
        // Order changed to paid by delayed functionality

        if ($orderLocker->isLockedOrder($orderNumber)) {
          $orderLocker->unlockOrder($orderNumber);
        }

        return parent::onReturn($order, $request);
      }
    }

    $gatewayId = $order->get('payment_gateway')->first()->entity->id();

    try {

      $totalTax = 0;

      foreach ($order->collectAdjustments() as $adjustment) {
        if ($adjustment->getType() === 'tax') {
          $totalTax += $adjustment->getAmount()->getNumber();
        }
      }

      $totalInclTax = $order->getTotalPrice()->getNumber();
      $totalExclTax = $totalInclTax - $totalTax;

      // order information
      $orderImpl = new OrderImpl(
        (string)$order->id(),
        gmdate('Y-m-d H:i:s', $order->getCreatedTime()),
        $this->_paymentHelper->convertCountryToISO4217($order->getTotalPrice()->getCurrencyCode()),
        (string)(round($totalInclTax, 2) * 100),
        (string)(round($totalExclTax, 2) * 100),
        (string)(round($totalTax, 2) * 100)
      );

      /** @var FrontendResponseServiceImpl $service */
      $service->insertOrder($orderImpl);
      $container = new ExecutorContainer(array('responseConversion.class' => 'Converter\Response\FrontendServiceResponseConverter'));
      $exec = $container->getExecutor(ExecutorContainer::EXECUTOR_TYPE_FRONTEND_RESPONSE);

      $gatewayKeyFile = $this->_paymentHelper->getKeyPath($gatewayId, $this->getConfiguration(), $this->defaultConfiguration(), $this->_paymentHelper::KEY_FILE_GATEWAY);

      /** @var CoreResponse $parseResponse */
      $parsedResponse = $exec->executeService($service, $gatewayKeyFile);

      /** @var PaymentResponseImpl $body */
      $responseBody = $parsedResponse->getBody();
      $validate = true;
    } catch (\Exception $e) {
      throw new PaymentGatewayException('Payment failed. Problem with validate response: ' . $e->getMessage());
    }

    if ($validate
      && $parsedResponse->getStatusCode() == CoreResponseConverter::STATUS_OK
      && empty($responseBody->getCancelMessage())
    ) {

      $trans_id = preg_replace("/[^0-9]+/", "", $responseBody->getTransactionNumber());
      $_transactionId = $responseBody->getTransactionNumber();

      $paymentMethod = $responseBody->getPaymentMethodCode();

      $paymentId = $_transactionId . self::TRANSACTION_ID_DELIMITER . $paymentMethod;

      // create payment
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      $existing_payments = $payment_storage->loadMultipleByOrder($order);

      $payment_logged = FALSE;
      foreach ($existing_payments as $payment) {
        if ($payment->getRemoteId() == $paymentId) {
          $payment_logged = TRUE;
          break;
        }
      }

      $amount = $responseBody->getOrderGrossAmount() / 100;

      if (!$payment_logged) {
        $payment = $payment_storage->create([
          'state' => 'completed',
          'amount' => new Price((string)$amount, $order->getTotalPrice()->getCurrencyCode()),
          'payment_gateway' => $this->entityId,
          'order_id' => $order->id(),
          'remote_id' => $paymentId,
          'completed' => $this->time->getRequestTime(),
        ]);

        $payment->save();

        $orderLocker->unlockOrder($orderNumber);

      }

    } else {
      throw new PaymentGatewayException('Payment failed.');
    }


    return parent::onReturn($order, $request);

  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request)
  {
    return parent::onCancel($order, $request);
  }

  public function getReturnUrl(OrderInterface $order)
  {
    return Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

  public function getCancelUrl(OrderInterface $order, $reason)
  {
    return Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
      'reason' => $reason
    ], ['absolute' => TRUE])->toString();
  }

  public function refundPayment(PaymentInterface $payment, Price $amount = NULL)
  {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $currency = $amount ? $amount->getCurrencyCode() : $payment->getAmount()->getCurrencyCode();

    $remoteId = explode(self::TRANSACTION_ID_DELIMITER, $payment->getRemoteId());
    $transactionId = $remoteId[0];
    $paymentMethod = $remoteId[1];

//    [$transactionId, $paymentMethod] = explode(self::TRANSACTION_ID_DELIMITER, $payment->getRemoteId());

    $refundAmount = $amount * 100;

    $transaction = new TransactionImpl(
      $paymentMethod,
      $transactionId,
      (string)$refundAmount,
      $this->_paymentHelper->convertCountryToISO4217($currency)
    );

    $gatewayId = $payment->getPaymentGatewayId();

    try {

      $configuration = $this->getConfiguration();

      $shopKeyFile = $this->_paymentHelper->getKeyPath($gatewayId, $configuration, $this->defaultConfiguration(), $this->_paymentHelper::KEY_FILE_SHOP);

      $configObject = new BackendConfigurationImpl(
        $shopKeyFile,
        $this->_paymentHelper->getMerchantId($gatewayId, $this->getConfiguration(), $this->defaultConfiguration()),
        $this->_paymentHelper->getSystemName(),
        $this->_paymentHelper->getModuleVersion(),
        $this->_paymentHelper->getUrls($configuration, 'server'),
        $configuration['disable_rsa_blinding']
      );

      /** @var RefundPaymentService $service */
      $service = ServiceFactory::createService($configObject, 'Backend\RefundPaymentService');
      $service->insertTransaction($transaction);
      $service->insertRefundProduct($transaction);

      $container = new ExecutorContainer();

      /** @var BackendServiceExecutor $exec */

      $exec = $container->getExecutor('backend');

      $gatewayKeyFilePath = $this->_paymentHelper->getKeyPath($gatewayId, $this->getConfiguration(), $this->defaultConfiguration(), $this->_paymentHelper::KEY_FILE_GATEWAY);

      /** @var CoreResponse $response */
      $response = $exec->executeService($service, $gatewayKeyFilePath);

      if ($response->getStatusCode()) {

        $old_refunded_amount = $payment->getRefundedAmount();
        $new_refunded_amount = $old_refunded_amount->add($amount);
        if ($new_refunded_amount->lessThan($payment->getAmount())) {
          $payment->setState('partially_refunded');
        } else {
          $payment->setState('refunded');
        }

        $payment->setRefundedAmount($new_refunded_amount);
        $payment->save();

      } else {
        throw new PaymentGatewayException('Can not refund. Please try again later');
      }
    } catch (\Exception $e) {
      throw $e;
    }

    return true;

  }
}
