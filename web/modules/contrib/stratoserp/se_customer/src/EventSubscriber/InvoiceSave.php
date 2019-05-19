<?php

namespace Drupal\se_customer\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hook_event_dispatcher\Event\Entity\EntityInsertEvent;
use Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\hook_event_dispatcher\Event\Entity\EntityUpdateEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\se_core\ErpCore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvoiceSave implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    /** @noinspection PhpDuplicateArrayKeysInspection */
    return [
      HookEventDispatcherInterface::ENTITY_INSERT => 'invoiceSave',
      HookEventDispatcherInterface::ENTITY_UPDATE => 'invoiceUpdate',
      HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'invoiceReduce'
    ];
  }

  public function invoiceSave(EntityInsertEvent $event) {
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() !== 'node'
      || $entity->bundle() !== 'se_invoice') {
      return;
    }
    $this->updateCustomerBalance($entity);
  }

  public function invoiceUpdate(EntityUpdateEvent $event) {
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() !== 'node'
      || $entity->bundle() !== 'se_invoice') {
      return;
    }
    $this->updateCustomerBalance($entity);
  }

  public function invoiceReduce(EntityPresaveEvent $event) {
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() !== 'node'
      || $entity->bundle() !== 'se_invoice'
      || $entity->isNew()) {
      return;
    }

    // Is this the right way?
    $this->updateCustomerBalance($entity, TRUE);
  }

  // On invoice
  private function updateCustomerBalance(EntityInterface $entity, $reduce = FALSE) {
    if (!$customer = \Drupal::service('se_customer.service')->lookupCustomer($entity)) {
      \Drupal::logger('se_customer_invoice_save')->error('No customer set for %node', ['%node' => $entity->id()]);
      return;
    }

    $amount = $entity->{'field_' . ErpCore::ITEMS_BUNDLE_MAP[$entity->bundle()] . '_total'}->value;
    if ($reduce) {
      $amount *= -1;
    }

    $balance = \Drupal::service('se_customer.service')->adjustBalance($customer, $amount);
  }

}
