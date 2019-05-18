<?php

namespace Drupal\braintree_cashier\EventSubscriber;

use Drupal\braintree_cashier\Event\BraintreeCashierEvents;
use Drupal\braintree_cashier\Event\BraintreeCustomerCreatedEvent;
use Drupal\braintree_cashier\Event\NewAccountAfterPlan;
use Drupal\braintree_cashier\Event\NewSubscriptionEvent;
use Drupal\braintree_cashier\Event\BraintreeErrorEvent;
use Drupal\braintree_cashier\Event\PaymentMethodUpdatedEvent;
use Drupal\braintree_cashier\Event\SubscriptionCanceledByUserEvent;
use Drupal\message\Entity\Message;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Create messages to record various Braintree Cashier events.
 */
class CreateMessageEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[BraintreeCashierEvents::NEW_ACCOUNT_AFTER_PLAN] = ['newAccountAfterPlan'];
    $events[BraintreeCashierEvents::NEW_SUBSCRIPTION] = ['newSubscription'];
    $events[BraintreeCashierEvents::PAYMENT_METHOD_UPDATED] = ['paymentMethodUpdated'];
    $events[BraintreeCashierEvents::BRAINTREE_CUSTOMER_CREATED] = ['braintreeCustomerCreated'];
    $events[BraintreeCashierEvents::SUBSCRIPTION_CANCELED_BY_USER] = ['subscriptionCanceledByUser'];
    $events[BraintreeCashierEvents::BRAINTREE_ERROR] = ['braintreeError'];
    return $events;
  }

  /**
   * Creates a message to record that a user had an error.
   *
   * The error occurs when trying to update or add a payment method, or when
   * trying to create a Braintree customer.
   *
   * @param \Drupal\braintree_cashier\Event\BraintreeErrorEvent $event
   *   The error event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function braintreeError(BraintreeErrorEvent $event) {
    $message = Message::create([
      'template' => 'braintree_error',
      'uid' => $event->getUser()->id(),
      'field_error_message' => $event->getErrorMessage(),
    ]);
    $message->save();
  }

  /**
   * Creates a message to record that a user canceled their subscription.
   *
   * The cancellation occurred in the UI.
   *
   * @param \Drupal\braintree_cashier\Event\SubscriptionCanceledByUserEvent $event
   *   The subscription canceled by user event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function subscriptionCanceledByUser(SubscriptionCanceledByUserEvent $event) {
    $subscription = $event->getSubscription();
    $message = Message::create([
      'template' => 'subscription_canceled_by_user',
      'uid' => $subscription->getSubscribedUser()->id(),
      'field_subscription' => $subscription->id(),
    ]);
    $message->save();
  }

  /**
   * Creates a message to record the creation of a Braintree customer.
   *
   * @param \Drupal\braintree_cashier\Event\BraintreeCustomerCreatedEvent $event
   *   The Braintree customer created event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function braintreeCustomerCreated(BraintreeCustomerCreatedEvent $event) {
    $message = Message::create([
      'template' => 'braintree_customer_created',
      'uid' => $event->getUser()->id(),
    ]);
    $message->save();
  }

  /**
   * Creates a message to record that the payment method was updated for a user.
   *
   * @param \Drupal\braintree_cashier\Event\PaymentMethodUpdatedEvent $event
   *   The payment updated event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function paymentMethodUpdated(PaymentMethodUpdatedEvent $event) {
    $message = Message::create([
      'template' => 'payment_method_updated',
      'uid' => $event->getUser()->id(),
      'field_payment_method_type' => $event->getPaymentMethodType(),
    ]);
    $message->save();
  }

  /**
   * Creates a message to record the new account and selected billing plan.
   *
   * @param \Drupal\braintree_cashier\Event\NewAccountAfterPlan $event
   *   The new account after plan selected event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function newAccountAfterPlan(NewAccountAfterPlan $event) {
    $account = $event->getAccount();
    $billing_plan = $event->getBillingPlan();
    $message = Message::create([
      'template' => 'account_created_after_plan',
      'uid' => $account->id(),
      'field_billing_plan' => $billing_plan->id(),
    ]);
    $message->save();
  }

  /**
   * Creates a message to record the creation of a new subscription.
   *
   * The applies to new customers who have never had a subscription before.
   *
   * @param \Drupal\braintree_cashier\Event\NewSubscriptionEvent $event
   *   The new subscription event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function newSubscription(NewSubscriptionEvent $event) {
    $billing_plan = $event->getBillingPlan();
    $subscription_entity = $event->getSubscriptionEntity();
    $account = $subscription_entity->getSubscribedUser();

    $message = Message::create([
      'template' => 'new_subscription',
      'uid' => $account->id(),
      'field_billing_plan' => $billing_plan->id(),
      'field_subscription' => $subscription_entity->id(),
    ]);
    $message->save();
  }

}
