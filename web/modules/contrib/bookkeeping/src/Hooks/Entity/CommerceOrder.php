<?php

namespace Drupal\bookkeeping\Hooks\Entity;

use Drupal\bookkeeping\Event\OrderTransactionEvent;
use Drupal\bookkeeping\Plugin\Field\FieldType\BookkeepingEntryItem;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Commerce Order hooks.
 */
class CommerceOrder {

  /**
   * The bookkeeping transaction entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The commerce order type entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderTypeStorage;

  /**
   * The bookkeeping commerce settings.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Constructs a commercepaymenthooks object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory, EventDispatcherInterface $event_dispatcher) {
    $this->storage = $entity_type_manager->getStorage('bookkeeping_transaction');
    $this->orderTypeStorage = $entity_type_manager->getStorage('commerce_order_type');
    $this->config = $config_factory->get('bookkeeping.commerce');
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Post save (insert/update) for orders.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_order\Entity\OrderInterface|null $original
   *   The original, if any.
   */
  public function postSave(OrderInterface $order, OrderInterface $original = NULL): void {
    $is_payable = $this->isPayable($order);
    $was_payable = $original && $this->isPayable($original);

    // If it was not and is not payable, there's nothing to do.
    if (!$is_payable && !$was_payable) {
      return;
    }

    // Get the amount to post. Start with the total price.
    $post_amount = $order->getTotalPrice();

    // If it was and is payable, we need to check for a difference.
    if ($is_payable && $was_payable) {
      $original_amount = $original->getTotalPrice();
      $generator = 'commerce_order:changed';
      if ($original_amount) {
        $post_amount = $post_amount->subtract($original_amount);
      }
    }
    // Otherwise if it is no longer payable, we need the negative.
    elseif ($was_payable) {
      $generator = 'commerce_order:unpayable';
      $post_amount = new Price(-$post_amount->getNumber(), $post_amount->getCurrencyCode());
    }
    // Otherwise we post the total price.
    else {
      $generator = 'commerce_order:payable';
    }

    // See where we should track income for this store.
    $store_id = $order->getStoreId();
    if ($this->config->get("stores.{$store_id}.disabled")) {
      return;
    }
    $accounts_receivable = $this->config->get("stores.{$store_id}.accounts_receivable_account");
    $income_account = $this->config->get("stores.{$store_id}.income_account");

    // Dispatch our event.
    $event = new OrderTransactionEvent(
      $generator,
      $post_amount,
      $income_account,
      $accounts_receivable,
      $order,
      $original
    );
    $this->eventDispatcher->dispatch(OrderTransactionEvent::EVENT, $event);

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

  /**
   * Check whether an order is payable (therefore should be tracked as income).
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order to check.
   *
   * @return bool
   *   Whether it is payable.
   */
  protected function isPayable(OrderInterface $order) {
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $this->orderTypeStorage->load($order->bundle());
    $payable_states = $order_type->getThirdPartySetting('bookkeeping', 'payable_states', ['completed']);
    return in_array($order->getState()->value, $payable_states);
  }

}
