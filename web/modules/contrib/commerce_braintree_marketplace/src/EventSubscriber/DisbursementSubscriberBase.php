<?php

namespace Drupal\commerce_braintree_marketplace\EventSubscriber;

use Drupal\commerce_braintree_marketplace\Event\BraintreeMarketplaceEvents;
use Drupal\commerce_braintree_marketplace\Event\DisbursementEvent;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DisbursementSubscriberBase
 *
 * @package Drupal\commerce_braintree_marketplace\EventSubscriber
 *
 * This class provides a base implementation of a disbursement event subscriber,
 * which by default marks disbursed payments as released.
 */
class DisbursementSubscriberBase implements EventSubscriberInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @inheritDoc
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Disbursement event subscriber.
   *
   * Mark payments with an escrow status as now released. This helps to control
   * available actions/transitions in the UI.
   *
   * @param \Drupal\commerce_braintree_marketplace\Event\DisbursementEvent $event
   */
  public function onDisbursement(DisbursementEvent $event) {
    if (!$event->getWebhook()->disbursement->success) {
      return;
    }
    $paymentIds = $this->entityTypeManager
      ->getStorage('commerce_payment')
      ->getQuery()
      ->condition('remote_id', $event->getTransactionIds(), 'IN')
      ->exists('escrow_status')
      ->execute();
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface[] $payments */
    $payments = Payment::loadMultiple($paymentIds);
    foreach ($payments as $payment) {
      $payment->set('escrow_status', 'released');
    }
  }

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      BraintreeMarketplaceEvents::DISBURSEMENT => ['onDisbursement'],
    ];
  }

}
