<?php

namespace Drupal\commerce_taxjar\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to handle syncing order data with TaxJar.
 */
class TaxJarTransactionSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CommitTransactionSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.commerce_order.presave' => ['updateTransaction'],
      'commerce_payment.refund.post_transition' => ['refundTransaction'],
      'commerce_order.commerce_order.delete' => ['deleteTransaction'],
    ];
    return $events;
  }

  /**
   * Creates / updates a transaction in TaxJar.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order update event.
   */
  public function updateTransaction(OrderEvent $event) {
    $order = $event->getOrder();

    $taxjar_data = $order->getData('taxjar');

    if (!empty($taxjar_data)) {
      $tax_type = $this->entityTypeManager->getStorage('commerce_tax_type')->load($taxjar_data['plugin_id']);
      $plugin = $tax_type->getPlugin();

      if ($plugin->getConfiguration()['enable_reporting'] && !empty($order->original)) {
        // Order created.
        if ($order->original->getState()->value === 'draft' && $order->getState()->value !== 'draft') {
          $plugin->createTransaction($order);
        }
        // Order updated.
        elseif ($order->original->getState()->value !== 'draft' && $order->getState()->value !== 'draft') {
          // Manually recalculate total prior to updating the transaction.
          $order->recalculateTotalPrice();
          $plugin->updateTransaction($order);
        }
      }
    }
  }

  /**
   * Refunds a transaction in TaxJar.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function refundTransaction(WorkflowTransitionEvent $event) {
    $payment = $event->getEntity();
    $order = $payment->getOrder();
    $amount = $payment->getRefundedAmount()->getNumber();

    $taxjar_data = $order->getData('taxjar');

    if (!empty($taxjar_data)) {
      $tax_type = $this->entityTypeManager->getStorage('commerce_tax_type')->load($taxjar_data['plugin_id']);
      $plugin = $tax_type->getPlugin();

      if ($plugin->getConfiguration()['enable_reporting']) {
        $plugin->refundTransaction($order, $amount);
      }
    }
  }

  /**
   * Deletes a transaction in TaxJar.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order delete event.
   */
  public function deleteTransaction(OrderEvent $event) {
    $order = $event->getOrder();

    $taxjar_data = $order->getData('taxjar');

    if (!empty($taxjar_data)) {
      $tax_type = $this->entityTypeManager->getStorage('commerce_tax_type')->load($taxjar_data['plugin_id']);
      $plugin = $tax_type->getPlugin();

      if ($plugin->getConfiguration()['enable_reporting']) {
        $plugin->deleteTransaction($order);
      }
    }
  }

}
