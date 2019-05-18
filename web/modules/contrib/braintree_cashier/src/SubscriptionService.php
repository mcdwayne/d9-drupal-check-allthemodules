<?php

namespace Drupal\braintree_cashier;

use Braintree\Result\Error;
use Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface;
use Drupal\braintree_cashier\Entity\BraintreeCashierSubscription;
use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\braintree_cashier\Event\BraintreeCashierEvents;
use Drupal\braintree_cashier\Event\BraintreeErrorEvent;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use Drupal\braintree_api\BraintreeApiService;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Parser\DecimalMoneyParser;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SubscriptionService.
 */
class SubscriptionService {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Drupal\braintree_api\BraintreeApiService definition.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * The subscription entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $subscriptionStorage;

  /**
   * The Braintree cashier config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The decimal money parser.
   *
   * @var \Money\Parser\DecimalMoneyParser
   */
  protected $moneyParser;

  /**
   * The decimal money formatter.
   *
   * @var \Money\Formatter\DecimalMoneyFormatter
   */
  protected $decimalMoneyFormatter;

  /**
   * The braintree cashier service.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The billable user service.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * The currency code.
   *
   * @var string
   */
  protected $currencyCode;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The discount entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $discountStorage;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new SubscriptionService object.
   *
   * @param \Drupal\Core\Logger\LoggerChannel $logger_channel_braintree_cashier
   *   The braintree_cashier logger channel.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\braintree_api\BraintreeApiService $braintree_api_braintree_api
   *   The Braintree API service.
   * @param \Drupal\braintree_cashier\BraintreeCashierService $bcService
   *   The braintree cashier service.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\braintree_cashier\BillableUser $billableUser
   *   The billable user service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher
   *   The container aware event dispatcher.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(LoggerChannel $logger_channel_braintree_cashier, EntityTypeManagerInterface $entity_type_manager, BraintreeApiService $braintree_api_braintree_api, BraintreeCashierService $bcService, ConfigFactory $configFactory, RequestStack $requestStack, BillableUser $billableUser, ModuleHandlerInterface $moduleHandler, ContainerAwareEventDispatcher $eventDispatcher, DateFormatterInterface $dateFormatter, MessengerInterface $messenger) {
    $this->logger = $logger_channel_braintree_cashier;
    $this->subscriptionStorage = $entity_type_manager->getStorage('braintree_cashier_subscription');
    $this->discountStorage = $entity_type_manager->getStorage('braintree_cashier_discount');
    $this->braintreeApi = $braintree_api_braintree_api;
    $this->bcService = $bcService;
    $this->config = $configFactory->get('braintree_cashier.settings');
    $this->requestStack = $requestStack;
    $this->billableUser = $billableUser;
    $this->moduleHandler = $moduleHandler;

    // Setup Money.
    $currencies = new ISOCurrencies();
    $this->moneyParser = new DecimalMoneyParser($currencies);
    $this->decimalMoneyFormatter = new DecimalMoneyFormatter($currencies);

    $this->currencyCode = $this->config->get('currency_code');

    $this->eventDispatcher = $eventDispatcher;
    $this->dateFormatter = $dateFormatter;
    $this->messenger = $messenger;
  }

  /**
   * Cancels the subscription.
   *
   * It will remain active until the end of the currnt period.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription
   *   The subscription entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function cancel(BraintreeCashierSubscriptionInterface $subscription) {
    if ($this->isBraintreeManaged($subscription)) {
      $braintree_subscription = $this->asBraintreeSubscription($subscription);

      // Cancel now to avoid any more charge attempts on a subscription that is
      // past due.
      if ($braintree_subscription->status === \Braintree_Subscription::PAST_DUE) {
        $this->cancelNow($subscription);
        return;
      }

      if (empty($braintree_subscription->billingPeriodEndDate)) {
        // The billingPeriodEndDate is empty for free trials.
        $this->braintreeApi->getGateway()->subscription()->cancel($braintree_subscription->id);
        $subscription->setPeriodEndDate($braintree_subscription->firstBillingDate->getTimestamp());
      }
      else {
        // Make the current billing cycle the last billing cycle.
        $this->braintreeApi->getGateway()->subscription()->update($braintree_subscription->id, [
          'numberOfBillingCycles' => $braintree_subscription->currentBillingCycle,
        ]);
      }
    }
    $subscription->setCancelAtPeriodEnd(TRUE);
    $subscription->save();
  }

  /**
   * Gets whether the subscription entity is managed by Braintree.
   *
   * A subscription on a free trial that will cancel at period end is not
   * managed by Braintree since the corresponding Braintree subscription will
   * have already been canceled.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription
   *   The subscription entity.
   *
   * @return bool
   *   A boolean indicating whether the subscription is managed by Braintree.
   */
  public function isBraintreeManaged(BraintreeCashierSubscriptionInterface $subscription) {
    return !empty($subscription->getBraintreeSubscriptionId()) && !($subscription->isTrialing() && $subscription->willCancelAtPeriodEnd());
  }

  /**
   * Get the subscription as a Braintree subscription object.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription
   *   The subscription entity.
   *
   * @return \Braintree_Subscription
   *   The braintree subscription object.
   */
  public function asBraintreeSubscription(BraintreeCashierSubscriptionInterface $subscription) {
    return $this->braintreeApi->getGateway()->subscription()->find($subscription->getBraintreeSubscriptionId());
  }

  /**
   * Cancels the subscription immediately.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription
   *   The subscription entity to cancel.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function cancelNow(BraintreeCashierSubscriptionInterface $subscription) {
    if ($this->isBraintreeManaged($subscription)) {
      $braintree_subscription = $this->asBraintreeSubscription($subscription);
      $this->braintreeApi->getGateway()->subscription()->cancel($braintree_subscription->id);
    }
    $subscription->setStatus(BraintreeCashierSubscriptionInterface::CANCELED);
    $subscription->save();
  }

  /**
   * Swap a subscription between billing plans.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription_entity
   *   The subscription entity.
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan
   *   The billing plan entity to swap to.
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The updated, or new, subscription entity.
   *
   * @throws \Exception
   */
  public function swap(BraintreeCashierSubscriptionInterface $subscription_entity, BraintreeCashierBillingPlanInterface $billing_plan, User $user) {
    $braintree_subscription = $this->asBraintreeSubscription($subscription_entity);
    if ($this->onGracePeriod($subscription_entity) && $braintree_subscription->planId == $billing_plan->getBraintreePlanId()) {
      return $this->resume($subscription_entity);
    }
    if ($this->wouldChangeBillingFrequency($braintree_subscription, $billing_plan)) {
      return $this->swapAcrossFrequencies($braintree_subscription, $billing_plan, $user);
    }

    $new_braintree_plan = $this->bcService->getBraintreeBillingPlan($billing_plan->getBraintreePlanId());

    $result = $this->braintreeApi->getGateway()->subscription()->update($braintree_subscription->id, [
      'planId' => $billing_plan->getBraintreePlanId(),
      'neverExpires' => TRUE,
      'price' => $new_braintree_plan->price,
      'numberOfBillingCycles' => NULL,
      'options' => [
        'prorateCharges' => TRUE,
      ],
    ]);

    if ($result->success) {
      $new_braintree_subscription = $result->subscription;
      return $this->updateSubscriptionEntityBillingPlan($subscription_entity, $billing_plan, $new_braintree_subscription);
    }
    else {
      $event = new BraintreeErrorEvent($user, $result->message, $result);
      $this->eventDispatcher->dispatch(BraintreeCashierEvents::BRAINTREE_ERROR, $event);
    }
  }

  /**
   * Determine if the subscription will cancel at period end but is active.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription
   *   The subscription entity.
   *
   * @return bool
   *   A boolean indicating whether the subscription is on it's grace period.
   */
  public function onGracePeriod(BraintreeCashierSubscriptionInterface $subscription) {
    return $subscription->willCancelAtPeriodEnd() && $subscription->getStatus() == BraintreeCashierSubscriptionInterface::ACTIVE;
  }

  /**
   * Resumes a subscription that on it's grace period.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription
   *   The subscription entity.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function resume(BraintreeCashierSubscriptionInterface $subscription) {
    if (!$this->onGracePeriod($subscription)) {
      throw new \LogicException('Unable to resume subscription that is not within grace period.');
    }
    $braintree_subscription = $this->asBraintreeSubscription($subscription);
    $this->braintreeApi->getGateway()->subscription()->update($braintree_subscription->id, [
      'neverExpires' => TRUE,
      'numberOfBillingCycles' => NULL,
    ]);
    $subscription->setCancelAtPeriodEnd(FALSE);
    $subscription->save();

    return $subscription;
  }

  /**
   * Determines if the given plan would alter the billing frequency.
   *
   * @param \Braintree_Subscription $current_subscription
   *   The subscription entity.
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan
   *   The billing plan entity.
   *
   * @return bool
   *   A boolean indicating whether the billing frequency would change.
   *
   * @throws \Exception
   */
  protected function wouldChangeBillingFrequency(\Braintree_Subscription $current_subscription, BraintreeCashierBillingPlanInterface $billing_plan) {
    $current_plan = $this->bcService->getBraintreeBillingPlan($current_subscription->planId);
    $target_plan = $this->bcService->getBraintreeBillingPlan($billing_plan->getBraintreePlanId());
    return $current_plan->billingFrequency != $target_plan->billingFrequency;
  }

  /**
   * Swap subscriptions for a user across different billing frequencies.
   *
   * Since Braintree doesn't support this natively, we need to cancel the old
   * subscription and create a new one. We give prorated credit from the old
   * subscription to the new one.
   *
   * @param \Braintree_Subscription $current_braintree_subscription
   *   The old Braintree subscription that will be canceled.
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan
   *   The target billing plan for which to create a new subscription.
   * @param \Drupal\user\Entity\User $user
   *   The user for whom the new subscription will be created.
   *
   * @return bool|\Drupal\braintree_cashier\Entity\SubscriptionInterface|false
   *   The subscription entity, or FALSE on failure.
   *
   * @throws \Braintree\Exception\NotFound
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function swapAcrossFrequencies(\Braintree_Subscription $current_braintree_subscription, BraintreeCashierBillingPlanInterface $billing_plan, User $user) {

    $current_braintree_plan = $this->bcService->getBraintreeBillingPlan($current_braintree_subscription->planId);
    $target_braintree_plan = $this->bcService->getBraintreeBillingPlan($billing_plan->getBraintreePlanId());
    if ($this->switchingMonthlyToYearlyPlan($current_braintree_plan, $target_braintree_plan)) {
      $discount = $this->getDiscountForSwitchToYearlyPlan($current_braintree_subscription);
    }
    else {
      $message = $this->t('Switching between plans with these billing frequencies is not supported. You may only switch between plans with the same billing frequency, or switch from a monthly to a yearly plan. Please try switching to a different plan, or wait until your current plan expires and then purchase another one.');
      $this->messenger->addError($message);
      $this->logger->error($message . ' ' . $this->t('Current Braintree subscription ID: %sid, target Braintree billing plan ID, %pid',
          [
            '%sid' => $current_braintree_subscription->id,
            '%pid' => $target_braintree_plan->id,
          ]));
      return FALSE;
    }

    $options = [];
    if ($discount['amount']->greaterThan($this->moneyParser->parse('0', $this->currencyCode))) {
      $options = [
        'discounts' => [
          'add' => [
            [
              'inheritedFromId' => 'plan-credit',
              'amount' => $this->decimalMoneyFormatter->format($discount['amount']),
              'numberOfBillingCycles' => $discount['number_of_billing_cycles'],
            ],
          ],
        ],
      ];
    }

    // Create a new Braintree subscription.
    $payment_method = $this->billableUser->getPaymentMethod($user);
    $new_braintree_subscription = $this->createBraintreeSubscription($user, $payment_method->token, $billing_plan, $options);

    if (empty($new_braintree_subscription)) {
      return FALSE;
    }
    // Cancel the old subscription entity, whereby we also cancel the old
    // Braintree subscription.
    $old_subscription_entity = $this->findSubscriptionEntity($current_braintree_subscription->id);
    $this->cancelNow($old_subscription_entity);

    // Create a new subscription entity.
    $new_subscription_entity = $this->createSubscriptionEntity($billing_plan, $user, $new_braintree_subscription);
    $new_subscription_entity->save();

    return $new_subscription_entity;
  }

  /**
   * Determines if the user is switching form monthly to yearly billing.
   *
   * @param \Braintree_Plan $current_plan
   *   The current billing plan entity.
   * @param \Braintree_Plan $target_plan
   *   The billing plan entity to change to.
   *
   * @return bool
   *   A boolean indicating if the switch is from monthly to yearly billing.
   */
  protected function switchingMonthlyToYearlyPlan(\Braintree_Plan $current_plan, \Braintree_Plan $target_plan) {
    return $current_plan->billingFrequency == 1 && $target_plan->billingFrequency == 12;
  }

  /**
   * Creates a Braintree Subscription.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param string $token
   *   A payment method token.
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan
   *   The billing plan entity.
   * @param array $options
   *   An array of subscription options to add to the payload.
   * @param string $coupon
   *   A discount ID.
   *
   * @return bool|\Braintree_Subscription
   *   The braintree subscription entity, or false on failure.
   */
  public function createBraintreeSubscription(User $user, $token, BraintreeCashierBillingPlanInterface $billing_plan, array $options = [], $coupon = NULL) {

    $payload = array_merge([
      'paymentMethodToken' => $token,
      'planId' => $billing_plan->getBraintreePlanId(),
    ], $options);

    if (!empty($coupon)) {
      $payload = $this->addCouponToPayload($coupon, $payload);
    }

    $result = $this->braintreeApi->getGateway()->subscription()->create($payload);

    if (!$result->success) {
      $this->processBraintreeSubscriptionCreateFailure($result);
      $event = new BraintreeErrorEvent($user, $result->message, $result);
      $this->eventDispatcher->dispatch(BraintreeCashierEvents::BRAINTREE_ERROR, $event);
      return FALSE;
    }

    $this->logger->notice('A new Braintree Subscription has been created with Braintree Subscription ID: %id', [
      '%id' => $result->subscription->id,
    ]);

    if ($billing_plan->hasFreeTrial() && !$user->get('had_free_trial')->value) {
      $user->set('had_free_trial', TRUE);
      $user->save();
    }

    return $result->subscription;
  }

  /**
   * Process a failed attempt with Braintree to create a subscription.
   *
   * @param \Braintree\Result\Error $result
   *   The Braintree response object which indicates failure.
   *
   * @see https://developers.braintreepayments.com/reference/general/testing/php#test-amounts
   * to simulate processor error responses.
   */
  private function processBraintreeSubscriptionCreateFailure(Error $result) {

    // Check for validation failures created by the Braintree gateway.
    // @see https://developers.braintreepayments.com/reference/general/result-objects/php#error-results
    if (!empty($result->errors) && empty($result->transaction)) {
      $this->messenger->addError($this->t('This transaction failed with the following error message: %message', [
        '%message' => $result->message,
      ]));
      $admin_message = 'Braintree failed to create the subscription, with the following message: ' . $result->message . '. Technical error details: ';
      foreach ($result->errors->deepAll() as $error) {
        $admin_message .= $error->attribute . ": " . $error->code . " " . $error->message . '. ';
      }
      $this->logger->error($admin_message);
      // There's no need to check for other error types since this transaction
      // attempt would not have reached beyond the Braintree gateway.
      return;
    }

    if (!empty($result->transaction)) {
      $transaction = $result->transaction;
      $this->logger->error('The transaction failed with the following message reported: ' . $result->message);
      switch ($transaction->status) {
        case 'processor_declined':
          $this->bcService->handleProcessorDeclined($transaction->processorResponseCode, $transaction->processorResponseText);
          return;

        case 'processor_settlement_declined':
          $this->bcService->handleProcessorSettlementDeclined($transaction);
          return;

        case 'gateway_rejected':
          $this->bcService->handleGatewayRejected($transaction->gatewayRejectionReason);
          return;
      }
    }

    // The failure is a mystery if this point is reached.
    $this->logger->error('A mysterious transaction failure occurred: ' . $result->message);
    $this->messenger->addError($this->t("It wasn't possible to create a subscription. Our payment processor reported the following error: %error. You have not been charged. Please contact the site administrator.", [
      '%error' => $result->message,
    ]));
  }

  /**
   * Adds the coupon discount to the Braintree payload.
   *
   * @param string $coupon
   *   The coupon ID.
   * @param array $payload
   *   The payload array.
   *
   * @return array
   *   The payload array.
   */
  protected function addCouponToPayload($coupon, array $payload) {
    if (!isset($payload['discounts']['add'])) {
      $payload['discounts']['add'] = [];
    }

    $payload['discounts']['add'][] = [
      'inheritedFromId' => $coupon,
    ];

    return $payload;
  }

  /**
   * Find the subscription entity for a given braintree subscription id.
   *
   * @param string $braintree_subscription_id
   *   The subscription ID in the Braintree control panel.
   *
   * @return \Drupal\braintree_cashier\Entity\SubscriptionInterface
   *   The corresponding subscription entity.
   *
   * @throws \Exception
   */
  public function findSubscriptionEntity($braintree_subscription_id) {
    $query = $this->subscriptionStorage->getQuery();
    $query->condition('braintree_subscription_id', $braintree_subscription_id);
    $result = $query->execute();
    if (count($result) > 1) {
      $message = $this->t('More than one subscription found for id: %id', ['%id' => $braintree_subscription_id]);
      $this->logger->emergency($message);
      throw new \Exception($message);
    }
    if (empty($result)) {
      $message = $this->t('No subscription found for id: %id', ['%id' => $braintree_subscription_id]);
      $this->logger->emergency($message);
      throw new \Exception($message);
    }
    /** @var \Drupal\braintree_cashier\Entity\SubscriptionInterface $subscription */
    $subscription = $this->subscriptionStorage->load(array_shift($result));
    return $subscription;
  }

  /**
   * Creates an active subscription entity.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan
   *   The billing plan entity.
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param \Braintree_Subscription $braintree_subscription
   *   The subscription entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the sign up form.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface|false
   *   The subscription entity.
   */
  public function createSubscriptionEntity(BraintreeCashierBillingPlanInterface $billing_plan, User $user, \Braintree_Subscription $braintree_subscription, FormStateInterface $form_state = NULL) {
    $params = [
      'subscription_type' => $billing_plan->getSubscriptionType(),
      'subscribed_user' => $user->id(),
      'status' => BraintreeCashierSubscriptionInterface::ACTIVE,
      'name' => $billing_plan->getName(),
      'billing_plan' => $billing_plan->id(),
      'roles_to_assign' => $billing_plan->getRolesToAssign(),
      'roles_to_revoke' => $billing_plan->getRolesToRevoke(),
      'braintree_subscription_id' => $braintree_subscription->id,
      'is_trialing' => !empty($braintree_subscription->trialPeriod),
    ];

    if (!empty($braintree_subscription->trialPeriod)) {
      // Braintree subscription's do not have a trial start date property.
      $params['trial_start_date'] = time();
    }

    if (!empty($braintree_subscription->discounts)) {
      $discount_braintree_ids = [];
      foreach ($braintree_subscription->discounts as $braintree_discount) {
        $discount_braintree_ids[] = $braintree_discount->id;
      }

      if (!empty($discount_braintree_ids)) {
        $discount_entity_ids = $this->discountStorage->getQuery()
          ->condition('discount_id', $discount_braintree_ids, 'IN')
          ->execute();

        if (!empty($discount_entity_ids)) {
          $params['discount'] = $discount_entity_ids;
        }
      }
    }

    $this->moduleHandler->alter('braintree_cashier_create_subscription_params', $params, $billing_plan, $form_state);

    $subscription_entity = BraintreeCashierSubscription::create($params);

    /** @var \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations */
    $violations = $subscription_entity->validate();
    foreach ($violations as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      $admin_message = $this->t('Constraint validation failed when creating a subscription. Message: %message', [
        '%message' => $violation->getMessage(),
      ]);
      $this->logger->error($admin_message);
    }
    if ($violations->count() > 0) {
      $this->messenger->addError($this->t('An error occurred creating the subscription. Please contact the site administrator.'));
      return FALSE;
    }
    $subscription_entity->save();

    return $subscription_entity;
  }

  /**
   * Updates a subscription entity after swapping.
   *
   * Update the subscription entity when the Braintree subscription with which
   * it's associated is replaced with a Braintree subscription with a new
   * billing plan.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription
   *   The subscription entity that needs updating.
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $new_billing_plan
   *   The new billing plan used to modify the Braintree subscription.
   * @param \Braintree_Subscription $updated_braintree_subscription
   *   The updated Braintree subscription.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface
   *   The subscription entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateSubscriptionEntityBillingPlan(BraintreeCashierSubscriptionInterface $subscription, BraintreeCashierBillingPlanInterface $new_billing_plan, \Braintree_Subscription $updated_braintree_subscription) {
    // Cancel subscription entity in order to invoke hooks on cancellation such
    // as revoking Roles. These hooks shouldn't interact with the Braintree API.
    $subscription->setStatus(BraintreeCashierSubscriptionInterface::CANCELED);
    $subscription->save();

    $subscription->setStatus(BraintreeCashierSubscriptionInterface::ACTIVE)
      ->setCancelAtPeriodEnd(FALSE)
      ->setName($new_billing_plan->getName())
      ->setType($new_billing_plan->getSubscriptionType())
      ->setBillingPlan($new_billing_plan->id())
      ->setRolesToAssign($new_billing_plan->getRolesToAssign())
      ->setRolesToRevoke($new_billing_plan->getRolesToRevoke());

    if (!empty($updated_braintree_subscription->discounts)) {
      $discount_braintree_ids = [];
      foreach ($updated_braintree_subscription->discounts as $braintree_discount) {
        $discount_braintree_ids[] = $braintree_discount->id;
      }

      if (!empty($discount_braintree_ids)) {
        $discount_entity_ids = $this->discountStorage->getQuery()
          ->condition('discount_id', $discount_braintree_ids, 'IN')
          ->execute();

        if (!empty($discount_entity_ids)) {
          $subscription->set('discount', $discount_entity_ids);
        }
      }
    }

    $subscription->save();
    return $subscription;
  }

  /**
   * Gets the money remaining in the current period.
   *
   * @param \Braintree_Subscription $current_subscription
   *   The current Braintree subscription from which to switch.
   *
   * @return \Money\Money
   *   The amount of money remaining in the current period.
   */
  public function moneyRemainingInCurrentPeriod(\Braintree_Subscription $current_subscription) {
    $current_period_start_date = $current_subscription->billingPeriodStartDate->getTimestamp();
    $current_period_end_date = $current_subscription->nextBillingDate->getTimestamp();
    // The multiplier is the fraction of time remaining in the current period.
    $multiplier = ($current_period_end_date - time()) / ($current_period_end_date - $current_period_start_date);
    $amount = $this->moneyParser->parse($current_subscription->price, $this->currencyCode);
    return $amount->multiply($multiplier);
  }

  /**
   * Gets the discount to apply for a switch to a yearly plan.
   *
   * @param \Braintree_Subscription $current_subscription
   *   The current Braintree subscription.
   *
   * @return array
   *   A discount array with keys:
   *   - 'amount': Money object representing the amount of the discount.
   *   - 'number_of_billing_periods': A natural number representing the number
   *     of periods over which to apply the discount.
   */
  public function getDiscountForSwitchToYearlyPlan(\Braintree_Subscription $current_subscription) {
    $amount = $this->moneyRemainingInCurrentPeriod($current_subscription);

    return [
      'amount' => $amount,
      'number_of_billing_cycles' => 1,
    ];
  }

  /**
   * Gets the period end date of the current subscription.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $current_subscription
   *   The current subscription entity.
   *
   * @return string
   *   The 'html_date' formatted period end date.
   */
  public function getFormattedPeriodEndDate(BraintreeCashierSubscriptionInterface $current_subscription) {
    $timestamp = '';
    if ($this->isBraintreeManaged($current_subscription)) {
      $braintree_subscription = $this->braintreeApi->getGateway()->subscription()->find($current_subscription->getBraintreeSubscriptionId());
      if (empty($braintree_subscription->billingPeriodEndDate)) {
        // The subscription must be on a free trial.
        $timestamp = $braintree_subscription->nextBillingDate->getTimestamp();
      }
      else {
        $timestamp = $braintree_subscription->billingPeriodEndDate->getTimestamp();
      }
    }
    elseif (!empty($current_subscription->getPeriodEndDate())) {
      $timestamp = $current_subscription->getPeriodEndDate();
    }
    $data = [
      'formatted_period_end_date' => !empty($timestamp) ? $this->dateFormatter->format($timestamp, 'long') : '',
      'timestamp' => $timestamp,
      'subscription_entity' => $current_subscription,
    ];
    $this->moduleHandler->alter('braintree_cashier_formatted_period_end_date', $data);
    return $data['formatted_period_end_date'];
  }

}
