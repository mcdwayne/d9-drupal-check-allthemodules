<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\BillableUser;
use Drupal\braintree_cashier\BraintreeCashierService;
use Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface;
use Drupal\braintree_cashier\Event\BraintreeCashierEvents;
use Drupal\braintree_cashier\Event\NewSubscriptionEvent;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form controller to confirm a change to an existing subscription.
 *
 * @ingroup braintree_cashier
 */
class UpdateSubscriptionFormConfirm extends ConfirmFormBase {

  /**
   * Drupal\braintree_cashier\BillableUser definition.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;
  /**
   * Drupal\braintree_cashier\SubscriptionService definition.
   *
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;
  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;
  /**
   * Drupal\braintree_cashier\BraintreeCashierService definition.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * The user entity for which a subscription is being changed.
   *
   * @var \Drupal\user\Entity|User
   */
  protected $account;

  /**
   * The Billing Plan entity to which the user's subscription will be updated.
   *
   * @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface
   */
  protected $billingPlan;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * UpdateSubscriptionFormConfirm constructor.
   *
   * @param \Drupal\braintree_cashier\BillableUser $braintree_cashier_billable_user
   *   The billable user service.
   * @param \Drupal\braintree_cashier\SubscriptionService $braintree_cashier_subscription_service
   *   The subscription service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The braintree_cashier logger channel.
   * @param \Drupal\braintree_cashier\BraintreeCashierService $braintree_cashier_braintree_cashier_service
   *   The generic braintree_cashier service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\braintree_api\BraintreeApiService $braintree_api
   *   The braintree API service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(BillableUser $braintree_cashier_billable_user, SubscriptionService $braintree_cashier_subscription_service, LoggerChannelInterface $logger, BraintreeCashierService $braintree_cashier_braintree_cashier_service, RequestStack $requestStack, EntityTypeManagerInterface $entity_type_manager, BraintreeApiService $braintree_api, EventDispatcherInterface $eventDispatcher) {
    $this->billableUser = $braintree_cashier_billable_user;
    $this->subscriptionService = $braintree_cashier_subscription_service;
    $this->logger = $logger;
    $this->bcService = $braintree_cashier_braintree_cashier_service;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_cashier.billable_user'),
      $container->get('braintree_cashier.subscription_service'),
      $container->get('logger.channel.braintree_cashier'),
      $container->get('braintree_cashier.braintree_cashier_service'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('braintree_api.braintree_api'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_subscription_form_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to switch your subscription plan?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Your new plan will be: %plan_description', [
      '%plan_description' => $this->billingPlan->getDescription(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('braintree_cashier.my_subscription', [
      'user' => $this->account->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL, BraintreeCashierBillingPlanInterface $billing_plan = NULL, $coupon_code = NULL) {

    $this->account = $user;
    $this->billingPlan = $billing_plan;

    $form = parent::buildForm($form, $form_state);

    $form['coupon_code'] = [
      '#type' => 'value',
      '#value' => $coupon_code,
    ];

    $form['actions']['submit']['#attributes']['id'] = 'submit-button';

    $form['#attached']['library'][] = 'braintree_cashier/update_confirm';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!empty($form_state->getValue('coupon_code')) && !$this->bcService->discountExists($this->billingPlan, $form_state->getValue('coupon_code'))) {
      $form_state->setErrorByName('coupon_code', t('The coupon code %coupon_code is invalid', [
        '%coupon_code' => $form_state->getValue('coupon_code'),
      ]));
    }
    // Validate that the new plan is not the same as the plan for a currently
    // active subscription.
    if (!empty($subscriptions = $this->billableUser->getSubscriptions($this->account))) {
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription */
      $subscription = array_shift($subscriptions);
      // If the subscription is on a grace period then validation will
      // succeed since the subscription needs to resume.
      // @see \Drupal\braintree_cashier\SubscriptionService::resume
      if (!$this->subscriptionService->onGracePeriod($subscription) && $subscription->getBillingPlan()->id() === $this->billingPlan->id()) {
        $form_state->setErrorByName('billing_plan', t('You already have an active subscription with the %billing_plan plan. No changes have been made.', [
          '%billing_plan' => $this->billingPlan->getDescription(),
        ]));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('braintree_cashier.my_subscription', [
      'user' => $this->account->id(),
    ]);
    $success_message = t('Your subscription has been updated!');

    $payload_options = [];
    if (!empty($subscriptions = $this->billableUser->getSubscriptions($this->account))) {
      // An active subscription exists, so swap it.
      if (count($subscriptions) > 1) {
        $message = 'An error has occurred. You have multiple active subscriptions. Please contact a site administrator.';
        $this->messenger()->addError($message);
        $this->logger->emergency($message);
        $this->bcService->sendAdminErrorEmail($message);
        return;
      }
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscription $subscription */
      $subscription = array_shift($subscriptions);
      if ($this->subscriptionService->isBraintreeManaged($subscription)) {
        $result = $this->subscriptionService->swap($subscription, $this->billingPlan, $this->account);
        if (!empty($result)) {
          $this->messenger()->addStatus($success_message);
          return;
        }
        else {
          $this->messenger()->addError(t('There was an error updating your subscription.'));
          $this->logger->error(t('Error updating subscription with entity ID: %subscription_id, for target billing plan: %billing_plan_id, for user ID: %uid', [
            '%subscription_id' => $subscription->id(),
            '%billing_plan_id' => $this->billingPlan->id(),
            '%uid' => $this->account->id(),
          ]));
          return;
        }
      }
      else {
        if ($subscription->isTrialing() && $subscription->willCancelAtPeriodEnd() && !empty($subscription->getPeriodEndDate())) {
          // Pro-rate the free trial duration by subtracting the number of days
          // of the currently active free trial from the number of days intended
          // by the Braintree Billing Plan for the new subscription.
          $braintree_billing_plan = $this->bcService->getBraintreeBillingPlan($this->billingPlan->getBraintreePlanId());
          if ($braintree_billing_plan->trialPeriod) {
            $trial_duration_days = $braintree_billing_plan->trialDuration;
            if ($braintree_billing_plan->trialDurationUnit == 'month') {
              $trial_duration_days *= 30;
            }
            $start = new \DateTime('@' . $subscription->getCreatedTime());
            $end = new \DateTime('@' . time());
            if ($end->diff($start)->format('%d') <= $trial_duration_days) {
              $payload_options['trialDuration'] = $trial_duration_days - $end->diff($start)->format('%d');
              $payload_options['trialDurationUnit'] = 'day';
            }
            else {
              $payload_options['trialPeriod'] = FALSE;
            }
          }
        }
        $this->subscriptionService->cancelNow($subscription);
      }
    }
    elseif ($this->billingPlan->hasFreeTrial()) {
      // Override the trialPeriod setting if the billing plan has free trials.
      // Each account gets only one free trial.
      $payload_options['trialPeriod'] = !$this->account->get('had_free_trial')->value;
    }

    $payment_method = $this->billableUser->getPaymentMethod($this->account);
    $coupon_code = $form_state->getValue('coupon_code');
    if (empty($braintree_subscription = $this->subscriptionService->createBraintreeSubscription($this->account, $payment_method->token, $this->billingPlan, $payload_options, $coupon_code))) {
      $this->messenger()->addError(t('You have not been charged.'));
      return;
    }

    $subscription_entity = $this->subscriptionService->createSubscriptionEntity($this->billingPlan, $this->account, $braintree_subscription);
    if (!$subscription_entity) {
      // A major constraint violation occurred while creating the
      // subscription.
      $message = t('An error occurred while creating the subscription. Unfortunately your payment method has already been charged. The site administrator has been notified, but you might wish to contact him or her yourself to troubleshoot the issue.');
      $this->messenger()->addError($message);
      $this->logger->emergency($message);
      $this->bcService->sendAdminErrorEmail($message);
      return;
    }

    $new_subscription_event = new NewSubscriptionEvent($braintree_subscription, $this->billingPlan, $subscription_entity);
    $this->eventDispatcher->dispatch(BraintreeCashierEvents::NEW_SUBSCRIPTION, $new_subscription_event);

    $this->messenger()->addStatus($success_message);
  }

}
