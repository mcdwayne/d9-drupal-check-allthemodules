<?php

namespace Drupal\braintree_cashier\Event;

/**
 * Events dispatched by the Braintree Cashier module.
 */
final class BraintreeCashierEvents {

  /**
   * A new subscription has been created.
   *
   * This does not include subscriptions created as a result of plan changes.
   *
   * @Event
   *
   * @var string
   */
  const NEW_SUBSCRIPTION = 'braintree_cashier.new_subscription';

  /**
   * A new user account has been created after selecting a billing plan.
   *
   * This is a step in the checkout flow.
   */
  const NEW_ACCOUNT_AFTER_PLAN = 'braintree_cashier.new_account_after_plan';

  /**
   * A user has updated their payment method.
   */
  const PAYMENT_METHOD_UPDATED = 'braintree_cashier.payment_method_updated';

  /**
   * A Braintree customer has been created for a user.
   */
  const BRAINTREE_CUSTOMER_CREATED = 'braintree_cashier.braintree_customer_created';

  /**
   * A subscription has been canceled by a user.
   *
   * The cancellation occurs in the UI. This event is dispatched in the form
   * controller.
   *
   * @see \Drupal\braintree_cashier\Form\CancelConfirmForm
   */
  const SUBSCRIPTION_CANCELED_BY_USER = 'braintree_cashier.subscription_canceled_by_user';

  /**
   * A payment method, or Braintree customer, or subscription create error.
   *
   * Serves to record errors interacting with the Braintree API.
   */
  const BRAINTREE_ERROR = 'braintree_cashier.braintree_error';

}
