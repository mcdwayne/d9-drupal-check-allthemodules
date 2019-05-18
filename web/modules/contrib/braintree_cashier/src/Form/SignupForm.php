<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\braintree_cashier\BillableUser;
use Drupal\braintree_cashier\BraintreeCashierService;
use Drupal\braintree_cashier\Event\BraintreeCashierEvents;
use Drupal\braintree_cashier\Event\NewSubscriptionEvent;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\braintree_api\BraintreeApiService;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SignupForm.
 */
class SignupForm extends PlanSelectFormBase {


  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The billable user service.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * The braintree cashier service.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * The subscription service.
   *
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new SignupForm object.
   *
   * @param \Drupal\braintree_api\BraintreeApiService $braintree_api
   *   The braintree api service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The braintree_cashier logger channel.
   * @param \Drupal\braintree_cashier\BraintreeCashierService $braintree_cashier_service
   *   The braintree cashier service.
   * @param \Drupal\braintree_cashier\BillableUser $billable_user
   *   The billable user service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\braintree_cashier\SubscriptionService $subscriptionService
   *   The subscription service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(BraintreeApiService $braintree_api, AccountProxy $current_user, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger, BraintreeCashierService $braintree_cashier_service, BillableUser $billable_user, RequestStack $requestStack, SubscriptionService $subscriptionService, EventDispatcherInterface $eventDispatcher) {
    parent::__construct($requestStack, $entity_type_manager, $braintree_api, $logger, $braintree_cashier_service);
    $this->currentUser = $current_user;
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->logger = $logger;
    $this->bcService = $braintree_cashier_service;
    $this->billableUser = $billable_user;
    $this->subscriptionService = $subscriptionService;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_api.braintree_api'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.braintree_cashier'),
      $container->get('braintree_cashier.braintree_cashier_service'),
      $container->get('braintree_cashier.billable_user'),
      $container->get('request_stack'),
      $container->get('braintree_cashier.subscription_service'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'signup_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // This hidden button will be the triggering element since it's first. This
    // is needed to distinguish between clicks on the "Confirm Coupon" button,
    // and the "Sign up" button.
    $form['final_submit'] = [
      '#type' => 'submit',
      '#name' => 'final_submit',
      '#attributes' => [
        'id' => 'final-submit',
        'class' => [
          'visually-hidden',
        ],
      ],
      '#submit' => [[$this, 'submitForm']],
    ];

    $form = parent::buildForm($form, $form_state);
    $user = NULL;
    if ($this->currentUser->isAuthenticated()) {
      $user = $this->userStorage->load($this->currentUser->id());
    }

    $form['dropin_container'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'dropin-container',
      ],
    ];

    $form['#attached']['library'][] = 'braintree_cashier/dropin_support';
    $form['#attached']['drupalSettings']['braintree_cashier'] = [
      'authorization' => $this->billableUser->generateClientToken($user),
      'acceptPaypal' => (bool) $this->config('braintree_cashier.settings')->get('accept_paypal'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit_signup',
      '#disabled' => TRUE,
      '#attributes' => [
        'id' => 'submit-button',
      ],
      '#value' => $this->t('Sign up!'),
    ];

    $form['payment_method_nonce'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'payment-method-nonce',
      ],
    ];

    $form['#attributes']['id'] = 'signup-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();

    if ($form_state->getTriggeringElement()['#name'] == 'final_submit') {
      if (empty($values['payment_method_nonce'])) {
        $message = t('The payment method could not be used. Please choose a different payment method.');
        $form_state->setErrorByName('payment_method_nonce', $message);
        $this->logger->error($message);
      }
      /** @var \Drupal\user\Entity\User $user */
      $user = $this->userStorage->load($this->currentUser->id());
      if ($this->currentUser->isAnonymous() || !empty($this->billableUser->getBraintreeCustomerId($user))) {
        $message = t('Can not process this form. Please contact a site administrator.');
        $form_state->setErrorByName('form_token', $message);
        // We should never get here since users with a Braintree customer ID,
        // should be redirected from this form to the My Subscription tab.
        // Anonymous users should be redirected to the Create New Account page.
        // @see \Drupal\braintree_cashier\EventSubscriber\KernelRequestSubscriber::kernelRequest
        $this->logger->emergency($message);
        $this->bcService->sendAdminErrorEmail($message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan */
    $billing_plan = $this->billingPlanStorage->load($values['plan_entity_id']);

    /** @var \Drupal\user\Entity\User $user */
    $user = $this->userStorage->load($this->currentUser->id());

    // Cancel any existing subscriptions, such as a free subscription
    // or an enterprise_individual subscription. There won't be any
    // subscriptions managed by Braintree since the user doesn't have a
    // Braintree customer ID.
    $subscriptions = $this->billableUser->getSubscriptions($user);
    foreach ($subscriptions as $subscription) {
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription */
      $this->subscriptionService->cancelNow($subscription);
    }

    if (empty($braintree_customer = $this->billableUser->createAsBraintreeCustomer($user, $values['payment_method_nonce']))) {
      $this->messenger()->addError($this->t('You have not been charged.'));
      $form_state->setRebuild();
      return;
    }

    $token = $braintree_customer->paymentMethods[0]->token;

    $coupon_code = NULL;
    if (!empty($values['coupon_code'])) {
      $coupon_code = $values['coupon_code'];
    }

    if (empty($braintree_subscription = $this->subscriptionService->createBraintreeSubscription($user, $token, $billing_plan, [], $coupon_code))) {
      $this->messenger()->addError($this->t('You have not been charged.'));
      $form_state->setRebuild();
      return;
    }

    $subscription_entity = $this->subscriptionService->createSubscriptionEntity($billing_plan, $user, $braintree_subscription, $form_state);
    if (!$subscription_entity) {
      // A major constraint violation occurred while creating the
      // subscription.
      $message = t('An error occurred while creating the subscription. Unfortunately your payment method has already been charged. The site administrator has been notified, but you might wish to contact him or her yourself to troubleshoot the issue.');
      $this->messenger()->addError($message);
      $form_state->setRebuild();
      $this->logger->emergency($message);
      $this->bcService->sendAdminErrorEmail($message);
      return;
    }

    $new_subscription_event = new NewSubscriptionEvent($braintree_subscription, $billing_plan, $subscription_entity);
    $this->eventDispatcher->dispatch(BraintreeCashierEvents::NEW_SUBSCRIPTION, $new_subscription_event);

    $this->messenger()->addStatus($this->t('You have been signed up for the %plan_name plan. Thank you, and enjoy your subscription!', [
      '%plan_name' => $billing_plan->getName(),
    ]));
  }

}
