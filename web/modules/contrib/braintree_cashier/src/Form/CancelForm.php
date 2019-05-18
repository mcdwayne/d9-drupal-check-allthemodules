<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\braintree_cashier\BillableUser;
use Drupal\Core\Logger\LoggerChannel;

/**
 * Class CancelForm.
 */
class CancelForm extends FormBase {

  /**
   * Drupal\braintree_api\BraintreeApiService definition.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * Drupal\braintree_cashier\SubscriptionService definition.
   *
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;

  /**
   * Drupal\braintree_cashier\BillableUser definition.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Constructs a new CancelForm object.
   */
  public function __construct(BraintreeApiService $braintree_api_braintree_api, SubscriptionService $braintree_cashier_subscription_service, BillableUser $braintree_cashier_billable_user, EntityTypeManagerInterface $entity_type_manager, LoggerChannel $logger_channel_braintree_cashier) {
    $this->braintreeApi = $braintree_api_braintree_api;
    $this->subscriptionService = $braintree_cashier_subscription_service;
    $this->billableUser = $braintree_cashier_billable_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_channel_braintree_cashier;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_api.braintree_api'),
      $container->get('braintree_cashier.subscription_service'),
      $container->get('braintree_cashier.billable_user'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.braintree_cashier')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_cancel_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {
    $form['uid'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    $form['message'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t('If you wish to cancel your subscription, just click the button below. You will continue to have access until the end of the current billing or free trial period.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel my subscription'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Redirect to confirmation.
    $values = $form_state->getValues();
    $form_state->setRedirect('braintree_cashier.cancel_confirm', [
      'user' => $values['uid'],
    ]);
  }

  /**
   * Access control handler for this route.
   */
  public function accessRoute(AccountInterface $browsing_account, User $user = NULL) {
    $is_allowed = $browsing_account->isAuthenticated() && !empty($user);
    $is_allowed = $is_allowed && ($browsing_account->id() == $user->id() || $browsing_account->hasPermission('administer braintree cashier'));
    foreach ($this->billableUser->getSubscriptions($user) as $subscription) {
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription */
      if (!$subscription->willCancelAtPeriodEnd()) {
        return AccessResultAllowed::allowedIf($is_allowed);
      }
    }
    return AccessResultForbidden::forbidden();
  }

}
