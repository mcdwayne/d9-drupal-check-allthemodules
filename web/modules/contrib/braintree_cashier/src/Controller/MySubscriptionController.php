<?php

namespace Drupal\braintree_cashier\Controller;

use Drupal\braintree_cashier\BraintreeCashierService;
use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\BillableUser;
use Drupal\Core\Logger\LoggerChannel;

/**
 * Class MySubscriptionController.
 */
class MySubscriptionController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Drupal\braintree_api\BraintreeApiService definition.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;
  /**
   * Drupal\braintree_cashier\BillableUser definition.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;
  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

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
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new MySubscriptionController object.
   */
  public function __construct(BraintreeApiService $braintree_api_braintree_api, BillableUser $braintree_cashier_billable_user, LoggerChannel $logger_channel_braintree_cashier, BraintreeCashierService $bcService, SubscriptionService $subscriptionService, DateFormatterInterface $dateFormatter) {
    $this->braintreeApi = $braintree_api_braintree_api;
    $this->billableUser = $braintree_cashier_billable_user;
    $this->logger = $logger_channel_braintree_cashier;
    $this->bcService = $bcService;
    $this->subscriptionService = $subscriptionService;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_api.braintree_api'),
      $container->get('braintree_cashier.billable_user'),
      $container->get('logger.channel.braintree_cashier'),
      $container->get('braintree_cashier.braintree_cashier_service'),
      $container->get('braintree_cashier.subscription_service'),
      $container->get('date.formatter')
    );
  }

  /**
   * View callback.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity being viewed.
   *
   * @return array
   *   A render array.
   */
  public function view(User $user) {

    // Don't cache this page since it can change both when the user adds a
    // payment method for the first time, and also when their subscription
    // changes.
    $build = [
      '#theme' => 'my_subscription',
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    $subscriptions = $this->billableUser->getSubscriptions($user);

    if (count($subscriptions) > 1) {
      $message = 'An error has occurred. You have multiple active subscriptions. Please contact a site administrator.';
      $this->messenger->addError($message);
      $this->logger->emergency($message);
      $this->bcService->sendAdminErrorEmail($message);
      return $build;
    }

    $has_no_subscription = empty($subscriptions);

    if ($has_no_subscription) {
      $current_subscription_label = $this->t('None');
    }
    else {
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription */
      $subscription = array_shift($subscriptions);
      $build['#current_subscription_entity'] = $subscription;
      $current_subscription_label = $subscription->label();
      if ($subscription->willCancelAtPeriodEnd()) {
        $build['#current_subscription_label__suffix'] = [
          '#markup' => '<p>' . $this->t('Billing has been canceled for this subscription. Access expires on %date', [
            '%date' => $this->subscriptionService->getFormattedPeriodEndDate($subscription),
          ]) . '</p>',
          '#allowed_tags' => ['p', 'a'],
        ];
      }
    }
    $build['#current_subscription_label'] = $current_subscription_label;

    // Show the update subscription form if the user has a payment method.
    if (!empty($this->billableUser->getBraintreeCustomerId($user))) {
      $build['#update_subscription_form'] = $this->formBuilder()->getForm('\Drupal\braintree_cashier\Form\UpdateSubscriptionForm', $user);
    }
    elseif ($has_no_subscription || (!$has_no_subscription && $subscription->getSubscriptionType() == BraintreeCashierSubscriptionInterface::FREE)) {
      $build['#signup_button'] = [
        '#type' => 'link',
        '#title' => $this->t('Sign up'),
        '#url' => Url::fromRoute('braintree_cashier.signup_form'),
        '#attributes' => ['class' => ['button', 'button--large']],
        '#prefix' => '<div class="signup-button">',
        '#suffix' => '</div>',
      ];
    }

    return $build;
  }

  /**
   * Access control handler for this route.
   *
   * @param \Drupal\Core\Session\AccountInterface $browsing_account
   *   The user account browsing.
   * @param \Drupal\user\Entity\User|null $user
   *   The user account being viewed.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function accessRoute(AccountInterface $browsing_account, User $user = NULL) {
    $is_allowed = $browsing_account->isAuthenticated() && !empty($user) && ($browsing_account->id() == $user->id() || $browsing_account->hasPermission('administer braintree cashier'));
    return AccessResultAllowed::allowedIf($is_allowed);
  }

}
