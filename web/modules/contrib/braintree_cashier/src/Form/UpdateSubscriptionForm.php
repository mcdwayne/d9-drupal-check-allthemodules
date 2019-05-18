<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\braintree_api\BraintreeApiService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\braintree_cashier\BillableUser;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\braintree_cashier\BraintreeCashierService;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class MySubscriptionForm.
 */
class UpdateSubscriptionForm extends PlanSelectFormBase {

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
   * The entity storage interface.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new MySubscriptionForm object.
   */
  public function __construct(BillableUser $braintree_cashier_billable_user, SubscriptionService $braintree_cashier_subscription_service, LoggerChannel $logger, BraintreeCashierService $braintree_cashier_braintree_cashier_service, RequestStack $requestStack, EntityTypeManagerInterface $entity_type_manager, BraintreeApiService $braintree_api) {
    parent::__construct($requestStack, $entity_type_manager, $braintree_api, $logger, $braintree_cashier_braintree_cashier_service);
    $this->billableUser = $braintree_cashier_billable_user;
    $this->subscriptionService = $braintree_cashier_subscription_service;
    $this->logger = $logger;
    $this->bcService = $braintree_cashier_braintree_cashier_service;
    $this->userStorage = $entity_type_manager->getStorage('user');
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
      $container->get('braintree_api.braintree_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_subscription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {

    $has_active_subscription = count($this->billableUser->getSubscriptions($user)) > 0;

    if ($has_active_subscription) {
      $header = $this->t('Want to modify your subscription?');
    }
    else {
      $header = $this->t('Sign up for a new subscription');
    }

    $form['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $header,
    ];

    $form['uid'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    $form = parent::buildForm($form, $form_state);
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $has_active_subscription ? $this->t('Update plan') : $this->t('Sign up!'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Redirect to confirmation.
    $values = $form_state->getValues();
    $params = [
      'user' => $values['uid'],
      'billing_plan' => $values['plan_entity_id'],
    ];
    if (!empty($values['coupon_code'])) {
      $params['coupon_code'] = $values['coupon_code'];
    }
    $form_state->setRedirect('braintree_cashier.update_confirm', $params);
  }

}
