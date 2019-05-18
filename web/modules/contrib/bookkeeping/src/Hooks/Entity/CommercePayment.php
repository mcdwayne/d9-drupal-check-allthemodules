<?php

namespace Drupal\bookkeeping\Hooks\Entity;

use Drupal\bookkeeping\Event\PaymentTransactionEvent;
use Drupal\bookkeeping\Plugin\Field\FieldType\BookkeepingEntryItem;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Commerce Payment hooks.
 */
class CommercePayment {

  /**
   * The bookkeeping transaction entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The bookkeeping commerce settings.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a commercepaymenthooks object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory, EventDispatcherInterface $event_dispatcher) {
    $this->storage = $entity_type_manager->getStorage('bookkeeping_transaction');
    $this->config = $config_factory->get('bookkeeping.commerce');
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Post save (insert/update) for payments.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param \Drupal\commerce_payment\Entity\PaymentInterface|null $original
   *   The original, if any.
   */
  public function postSave(PaymentInterface $payment, PaymentInterface $original = NULL): void {
    // If the payment is not complete, there's nothing to track.
    if (!$payment->isCompleted()) {
      return;
    }

    // Get the amount to post.
    $post_amount = $payment->getBalance();
    $generator = 'commerce_payment:completed';

    // If the payment was originally completed, we only post if there is a
    // difference in the amount.
    if ($original && $original->isCompleted()) {
      $generator = 'commerce_payment:changed';
      $original_amount = $original->getBalance();
      $post_amount = $post_amount->subtract($original_amount);
    }

    // See where we should track payments for this store.
    $order = $payment->getOrder();
    $store_id = $order->getStoreId();
    if ($this->config->get("stores.{$store_id}.disabled")) {
      return;
    }
    $accounts_receivable = $this->config->get("stores.{$store_id}.accounts_receivable_account");

    // See where we should track payments for this method.
    $gateway_id = $payment->getPaymentGatewayId();
    $payment_account = $this->config->get("payment_gateways.{$gateway_id}.asset_account");

    // Dispatch our event.
    $event = new PaymentTransactionEvent(
      $generator,
      $post_amount,
      $accounts_receivable,
      $payment_account,
      $order,
      $payment,
      $original
    );
    $this->eventDispatcher->dispatch(PaymentTransactionEvent::EVENT, $event);

    // If a subscriber asked us not to post, stop now.
    if ($event->isPrevented()) {
      return;
    }

    // Get our potentially modified post amount.
    $post_amount = $event->getValue();

    // If the amount is zero, there's nothing more to do.
    if ($post_amount->isZero()) {
      return;
    }

    // Create our transaction.
    /** @var \Drupal\bookkeeping\Entity\TransactionInterface $transaction */
    $transaction = $this->storage->create([
      'generator' => $generator,
    ]);

    // Add the entries.
    $transaction
      ->addEntry($event->getFrom(), $post_amount, BookkeepingEntryItem::TYPE_CREDIT)
      ->addEntry($event->getTo(), $post_amount, BookkeepingEntryItem::TYPE_DEBIT);

    // Add the related entities.
    foreach ($event->getRelated() as $related) {
      $transaction->addRelated($related);
    }

    // Save the transaction.
    $transaction->save();
  }

}
