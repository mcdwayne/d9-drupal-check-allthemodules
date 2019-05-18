<?php

namespace Drupal\commerce_funds\Plugin\Commerce\PaymentGateway;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_funds\Entity\Transaction;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_store\Resolver\DefaultStoreResolver;
use Drupal\commerce_funds\Services\TransactionManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Balance payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "funds_balance",
 *   label = @Translation("Funds balance"),
 *   display_label = @Translation("Funds balance"),
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_funds\PluginForm\Funds\BalanceMethodAddForm",
 *     "edit-payment-method" = "Drupal\commerce_funds\PluginForm\Funds\BalanceMethodAddForm",
 *   },
 *   payment_method_types = {"funds_wallet"},
 * )
 */
class BalanceGateway extends PaymentGatewayBase implements BalanceGatewayInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The default store resolver.
   *
   * @var \Drupal\commerce_store\Resolver\DefaultStoreResolver
   */
  protected $defaultStoreResolver;

  /**
   * The transaction manager.
   *
   * @var \Drupal\commerce_funds\Services\TransactionManager;
   */
  protected $transactionManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, DefaultStoreResolver $default_store_resolver, TransactionManager $transaction_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->defautStoreResolver = $default_store_resolver;
    $this->transactionManager = $transaction_manager;
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
      $container->get('commerce_store.default_store_resolver'),
      $container->get('commerce_funds.transaction_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    return $this->configuration['currency'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBalanceId() {
    return $this->configuration['balance_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $catpure = TRUE) {
    parent::assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    parent::assertPaymentMethod($payment_method);

    $this->doPayment($payment_method, $payment);
    $payment->setState('completed');
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $required_keys = ['balance_id', 'currency'];

    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }
    $payment_method->balance_id = $payment_details['balance_id'];
    $payment_method->currency = $payment_details['currency'];

    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    $payment_method->delete();
  }

  /**
   * Performs the payment operation.
   *
   * Remove total price from user balance and update site balance.
   *
   * @see createPaymentMethod()
   */
  protected function doPayment(PaymentMethodInterface $payment_method, PaymentInterface $payment) {
    $order = \Drupal::service('current_route_match')->getParameter('commerce_order');

    foreach ($order->getItems() as $item) {
      $fee = \Drupal::service('commerce_funds.fees_manager')->calculateTransactionFee($item->getTotalPrice()->getNumber(), $item->getTotalPrice()->getCurrencyCode(), 'payment');
      $transaction = Transaction::create([
        'issuer' => $payment_method->getOwnerId(),
        'recipient' => $item->getPurchasedEntity()->getOwnerId(),
        'type' => 'payment',
        'method' => $payment_method->bundle(),
        'brut_amount' => $item->getTotalPrice()->getNumber(),
        'net_amount' => $fee['net_amount'],
        'fee' => $fee['fee'],
        'currency' => $item->getTotalPrice()->getCurrencyCode(),
        'status' => 'Completed',
        'notes' => $this->t('Payment of <a href="product/@item">@item-name</a> for order <a href="user/@user/orders/@order">@order</a>', [
          '@item-name' => $item->getTitle(),
          '@item' => $item->getPurchasedEntityId(),
          '@user' => $payment_method->getOwnerId(),
          '@order' => $payment->getOrderId(),
        ]),
      ]);
      $transaction->save();

      $this->transactionManager->performTransaction($transaction);
    }
  }

}
