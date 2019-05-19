<?php

namespace Drupal\commerce_sofortbanking\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Annotation\CommercePaymentGateway;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Sofort\SofortLib\Notification;
use Sofort\SofortLib\Sofortueberweisung;
use Sofort\SofortLib\TransactionData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the SOFORT payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "sofort",
 *   label = "SOFORT",
 *   display_label = "SOFORT",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_sofortbanking\PluginForm\SofortGatewayForm",
 *   },
 * )
 */
class SofortGateway extends OffsitePaymentGatewayBase implements SofortGatewayInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

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
   * Constructs a new SofortGateway object.
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   The logger channel factory.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The price rounder.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, DateFormatterInterface $date_formatter, LoggerChannelFactoryInterface $logger_channel_factory, RounderInterface $rounder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->dateFormatter = $date_formatter;
    $this->logger = $logger_channel_factory->get('commerce_sofortbanking');
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
      $container->get('date.formatter'),
      $container->get('logger.factory'),
      $container->get('commerce_price.rounder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'configuration_key' => '',
        'reason_1' => 'Order {{order_id}}',
        'reason_2' => '',
        'buyer_protection' => FALSE,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['configuration_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Configuration key'),
      '#default_value' => $this->configuration['configuration_key'],
      '#required' => TRUE,
    ];

    $form['reason_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reason 1'),
      '#description' => $this->t('Determines the text to be displayed in the reason field of the transfer (max. 27 characters - special characters will be replaced/deleted). The following placeholders will be replaced by specific values:  {{order_id}}                         ==> Order number {{order_date}}                    ==> Order date {{customer_id}}                  ==> End customer number {{customer_name}}          ==> End customer name  {{customer_company}}   ==> Company name of end customer {{customer_email}}          ==> Email address of end customer {{transaction_id}}              ==> SOFORT Banking transaction ID'),
      '#default_value' => $this->configuration['reason_1'],
      '#required' => TRUE,
    ];

    $form['reason_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reason 2'),
      '#description' => $this->t('Determines the text to be displayed in the reason field of the transfer (max. 27 characters - special characters will be replaced/deleted). The following placeholders will be replaced by specific values:  {{order_id}}                         ==> Order number {{order_date}}                    ==> Order date {{customer_id}}                  ==> End customer number {{customer_name}}          ==> End customer name  {{customer_company}}   ==> Company name of end customer {{customer_email}}          ==> Email address of end customer {{transaction_id}}              ==> SOFORT Banking transaction ID'),
      '#default_value' => $this->configuration['reason_2'],
    ];

    $form['buyer_protection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable buyer protection.'),
      '#default_value' => $this->configuration['buyer_protection'],
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

      $this->configuration['configuration_key'] = $values['configuration_key'];
      $this->configuration['reason_1'] = $values['reason_1'];
      $this->configuration['reason_2'] = $values['reason_2'];
      $this->configuration['buyer_protection'] = $values['buyer_protection'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function initializeSofortApi(PaymentInterface $payment, array $form) {
    $sofort = new Sofortueberweisung($this->configuration['configuration_key']);

    $amount = $this->rounder->round($payment->getAmount());
    $sofort->setAmount($amount->getNumber());
    $sofort->setCurrencyCode($amount->getCurrencyCode());
    $reason1 = $this->replaceReasonPlaceholders($this->configuration['reason_1'], $payment);
    $reason2 = $this->replaceReasonPlaceholders($this->configuration['reason_2'], $payment);
    $sofort->setReason($reason1, $reason2);
    $sofort->setSuccessUrl($form['#return_url']);
    $sofort->setAbortUrl($form['#cancel_url']);
    $sofort->setTimeoutUrl($form['#cancel_url']);
    $sofort->setNotificationUrl($this->getNotifyUrl()->toString());
    $sofort->setCustomerprotection(!empty($this->configuration['buyer_protection']));
    $sofort->setApiVersion('2.0');
    // Sets a version string which help SOFORT to analyze server requests.
    $sofort->setVersion('drupal8_commerce_sofortbanking_2');

    return $sofort;
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    parent::onNotify($request);

    $sofort_notification = new Notification();
    $transaction_id = $sofort_notification->getNotification($request->getContent());
    $this->processNotification($transaction_id);
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    parent::onCancel($order, $request);

    $sofort_gateway_data = $order->getData('sofort_gateway');
    if (empty($sofort_gateway_data['transaction_id'])) {
      throw new InvalidRequestException('Transaction ID missing for this SOFORT transaction.');
    }

    $transaction_id = $sofort_gateway_data['transaction_id'];
    $payment = $this->paymentStorage->loadByRemoteId($transaction_id);
    if (empty($payment)) {
      throw new InvalidRequestException('No transaction found for SOFORT transaction ID @transaction_id.', ['@transaction_id' => $transaction_id]);
    }

    $payment->state = 'authorization_voided';
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    parent::onReturn($order, $request);

    $sofort_gateway_data = $order->getData('sofort_gateway');
    if (empty($sofort_gateway_data['transaction_id'])) {
      throw new InvalidRequestException('Transaction ID missing for this SOFORT transaction.');
    }

    $transaction_id = $sofort_gateway_data['transaction_id'];
    $this->processNotification($transaction_id);
  }

  /**
   * Replaces the placeholders of the given reason pattern string.
   *
   * @todo decide, if we should switch to real tokens instead.
   *
   * @param string $pattern
   *   The given pattern, as configured in the gateway's settings, either for
   *   "reason 1" or "reason 2".
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity, which values will be used for replacing the
   *   placeholders by actual values.
   *
   * @return string
   *   String having the placeholders replaced by real order values.
   */
  protected function replaceReasonPlaceholders($pattern, PaymentInterface $payment) {
    if (empty($pattern)) {
      return '';
    }
    $order = $payment->getOrder();
    /** @var \Drupal\address\AddressInterface|null $billing_address */
    $billing_address = $order->getBillingProfile()->hasField('address') && !$order->getBillingProfile()->get('address')->isEmpty() ? $order->getBillingProfile()->get('address')->first() : NULL;
    $customer_name = $billing_address ? sprintf($billing_address->getGivenName() . ' ' . $billing_address->getFamilyName()) : '';
    $customer_email = str_replace('@', ' ', $order->getEmail());
    $subject = str_replace('{{transaction_id}}', '-TRANSACTION-', $pattern);
    $subject = str_replace('{{order_id}}', $order->id(), $subject);
    $subject = str_replace('{{order_date}}', $this->dateFormatter->format($order->getChangedTime(), 'short'), $subject);
    $subject = str_replace('{{customer_id}}', $order->getCustomerId(), $subject);
    $subject = str_replace('{{customer_name}}', $customer_name, $subject);
    $subject = str_replace('{{customer_company}}', $billing_address ? $billing_address->getOrganization() : '', $subject);
    $subject = str_replace('{{customer_email}}', $customer_email, $subject);
    return $subject;
  }

  /**
   * Processes SOFORT response for both return url and async notification.
   *
   * @param string $transaction_id
   *   The SOFORT transaction ID to process.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The payment entity.
   *
   * @throws \Drupal\commerce_payment\Exception\InvalidRequestException
   *   If no payment entity could be found for the given transaction ID.
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   If the transaction status could not be queried from sofort.com.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   For the sake of completeness, as Drupal storage classes could
   *   theoretically throw this exception on load.
   */
  protected function processNotification($transaction_id) {
    $payment = $this->paymentStorage->loadByRemoteId($transaction_id);
    if (empty($payment)) {
      throw new InvalidRequestException('No transaction found for SOFORT transaction ID @transaction_id.', ['@transaction_id' => $transaction_id]);
    }

    $sofort_transaction = new TransactionData($this->configuration['configuration_key']);
    $sofort_transaction->addTransaction($transaction_id);
    $sofort_transaction->sendRequest();
    $sofort_transaction->setApiVersion('2.0');
    if ($sofort_transaction->isError()) {
      throw new PaymentGatewayException('Transaction status not available for SOFORT transaction ID @transaction_id (error: @error).', ['@transaction_id' => $transaction_id, '@error' => $sofort_transaction->getError()]);
    }

    $remote_state = $sofort_transaction->getStatus();
    $remote_state = empty($remote_state) ? 'cancel' : $remote_state;
    if ($remote_state == $payment->getRemoteState()) {
      // Nothing to do, this payment receipt has already been captured.
      return $payment;
    }

    $payment->setRemoteState($remote_state);
    switch ($remote_state) {
      case 'cancel':
        $payment->state = 'authorization_voided';
        break;

      case 'loss':
        $payment->state = 'authorization_voided';
        break;

      case 'pending':
        /*
         * Please note, that 'pending' means actually 'completed'. API doc:
         *
         * "Please keep in mind that if a SOFORT transaction has been
         *  successfully finished and SOFORT has received the confirmation from
         *  the buyer's bank, SOFORT reports the status
         *  "untraceable - sofort_bank_account_needed" (without Deutsche
         *  Handelsbank account) or "pending - not_credited_yet" (with Deutsche
         *  Handelsbank account). Both status messages have the equivalent
         *  meaning and represent the real-time transaction confirmation that
         *  should be processed by the merchant's online shop system."
         *
         * @see https://www.sofort.com/integrationCenter-eng-DE/content/view/full/2513/
         */
        $payment->state = 'completed';
        break;

      case 'received':
      case 'untraceable':
        $payment->state = 'completed';
        break;

      case 'refunded':
        $payment->state = $sofort_transaction->getStatusReason() == 'compensation' ? 'partially_refunded' : 'refunded';
        break;
    }
    $payment->save();
    return $payment;
  }

}
