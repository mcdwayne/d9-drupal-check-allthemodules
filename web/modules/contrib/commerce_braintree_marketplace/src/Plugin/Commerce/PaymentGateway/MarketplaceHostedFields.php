<?php

namespace Drupal\commerce_braintree_marketplace\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_braintree\ErrorHelper;
use Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway\HostedFields;
use Drupal\commerce_braintree_marketplace\Event\BraintreeMarketplaceEvents;
use Drupal\commerce_braintree_marketplace\Event\MarketplacePaymentEvent;
use Drupal\commerce_braintree_marketplace\Plugin\Commerce\PaymentType\SubMerchantPayment;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the HostedFields payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "braintree_hostedfields_marketplace",
 *   label = "Braintree (Hosted Fields - Marketplace)",
 *   display_label = "Braintree Marketplace",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_braintree\PluginForm\HostedFields\PaymentMethodAddForm",
 *   },
 *   js_library = "commerce_braintree/braintree",
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 *   payment_type = "payment_braintree_submerchant"
 * )
 */
class MarketplaceHostedFields extends HostedFields implements MarketplaceInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @inheritDoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, EventDispatcherInterface $eventDispatcher, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->eventDispatcher = $eventDispatcher;
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
      $container->get('event_dispatcher'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);
    $currency_code = $payment->getAmount()->getCurrencyCode();
    if (empty($this->configuration['merchant_account_id'][$currency_code])) {
      throw new InvalidRequestException(sprintf('No merchant account ID configured for currency %s', $currency_code));
    }

    $transaction_data = [
      'channel' => 'CommerceGuys_BT_Vzero',
      'merchantAccountId' => $this->configuration['merchant_account_id'][$currency_code],
      'orderId' => $payment->getOrderId(),
      'options' => [
        'submitForSettlement' => $capture,
      ],
    ];
    if ($payment_method->isReusable()) {
      $transaction_data['paymentMethodToken'] = $payment_method->getRemoteId();
    }
    else {
      $transaction_data['paymentMethodNonce'] = $payment_method->getRemoteId();
    }

    $event = new MarketplacePaymentEvent($payment, $transaction_data['options']);
    $this->eventDispatcher->dispatch(BraintreeMarketplaceEvents::PAYMENT, $event);
    if ($subMerchantId = $event->getMerchantId()) {
      $transaction_data['merchantAccountId'] = $subMerchantId;
      /** @var \Drupal\commerce_price\Plugin\Field\FieldType\PriceItem $serviceFee */
      $serviceFee = $payment->get('service_fee')->first();
      $transaction_data['serviceFeeAmount'] = $serviceFee->toPrice()->getNumber();
    }
    $transaction_data['options'] = $event->getTransactionOptions();
    if ($descriptor = $event->getCustomDescriptor()) {
      $transaction_data['descriptor']['name'] = $descriptor;
    }
    // Set the amount late in case an event altered the transaction amount.
    $transaction_data['amount'] = $payment->getAmount()->getNumber();
    try {
      $result = $this->api->transaction()->sale($transaction_data);
      ErrorHelper::handleErrors($result);
    }
    catch (\Braintree\Exception $e) {
      ErrorHelper::handleException($e);
    }

    $payment->setState($capture ? 'completed' : 'authorization');
    if (!$capture) {
      $payment->setAuthorizedTime(REQUEST_TIME);
    }
    $payment->setRemoteId($result->transaction->id);
    if ($escrowStatus = $result->transaction->escrowStatus) {
      $payment->set('escrow_status', $escrowStatus);
    }
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function holdInEscrow(PaymentInterface $payment) {
    try {
      $remote_id = $payment->getRemoteId();
      $result = $this->api->transaction()->holdInEscrow($remote_id);
    }
    catch (\Braintree\Exception $e) {
      ErrorHelper::handleException($e);
    }
    $payment->set('escrow_status', $result->transaction->escrowStatus);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function cancelRelease(PaymentInterface $payment) {
    try {
      $remote_id = $payment->getRemoteId();
      $result = $this->api->transaction()->cancelRelease($remote_id);
    }
    catch (\Braintree\Exception $e) {
      ErrorHelper::handleException($e);
    }
    $payment->set('escrow_status', $result->transaction->escrowStatus);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function releaseFromEscrow(PaymentInterface $payment) {
    try {
      $remote_id = $payment->getRemoteId();
      $result = $this->api->transaction()->releaseFromEscrow($remote_id);
    }
    catch (\Braintree\Exception $e) {
      ErrorHelper::handleException($e);
    }
    $payment->set('escrow_status', $result->transaction->escrowStatus);
    $payment->save();
  }

  /**
   * @inheritDoc
   */
  public function buildPaymentOperations(PaymentInterface $payment) {
    $operations = parent::buildPaymentOperations($payment);
    if (!($payment instanceof SubMerchantPayment)) {
      return $operations;
    }
    $escrowStatus = $payment->get('escrow_status')->first()->getString();
    $releaseAccess = $escrowStatus == 'held';
    $cancelReleaseAccess = $escrowStatus == 'release_pending';
    $holdAccess = !$escrowStatus;
    $operations['escrow-hold'] = [
      'title' => $this->t('Hold in Escrow'),
      'page_title' => $this->t('Hold payment in Escrow'),
      'plugin_form' => 'escrow-hold',
      'access' => $holdAccess,
    ];
    $operations['escrow-release'] = [
      'title' => $this->t('Release from Escrow'),
      'page_title' => $this->t('Release payment from Escrow'),
      'plugin_form' => 'escrow-release',
      'access' => $releaseAccess,
    ];
    $operations['escrow-release-cancel'] = [
      'title' => $this->t('Cancel Escrow Release'),
      'page_title' => $this->t('Cancel payment Escrow release'),
      'plugin_form' => 'escrow-release-cancel',
      'access' => $cancelReleaseAccess,
    ];
    return $operations;
  }

  /**
   * @inheritDoc
   */
  protected function getDefaultForms() {
    $forms = parent::getDefaultForms();
    $forms['escrow-hold'] = 'Drupal\commerce_braintree_marketplace\PluginForm\PaymentEscrowForm';
    $forms['escrow-release'] = 'Drupal\commerce_braintree_marketplace\PluginForm\PaymentEscrowReleaseForm';
    $forms['escrow-release-cancel'] = 'Drupal\commerce_braintree_marketplace\PluginForm\PaymentEscrowReleaseCancelForm';
    return $forms;
  }

}
