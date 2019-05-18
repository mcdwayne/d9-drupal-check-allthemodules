<?php

namespace Drupal\braintree_cashier\Controller;

use Drupal\braintree_cashier\BillableUser;
use Drupal\braintree_cashier\BraintreeCashierService;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Parser\DecimalMoneyParser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\braintree_api\BraintreeApiService;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class InvoicesController.
 */
class InvoicesController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Drupal\braintree_api\BraintreeApiService definition.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The decimal money parser.
   *
   * @var \Money\Parser\DecimalMoneyParser
   */
  protected $moneyParser;

  /**
   * The international money formatter.
   *
   * @var \Money\Formatter\IntlMoneyFormatter
   */
  protected $moneyFormatter;

  /**
   * A collection of successful payments or authorizations.
   *
   * @var \Braintree\ResourceCollection
   */
  protected $transactionSuccessfulCollection;

  /**
   * The immutable config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The braintree cashier service.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * The billable user service.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * The subscription service.
   *
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The braintree_cashier logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new InvoicesController object.
   */
  public function __construct(BraintreeApiService $braintree_api_braintree_api, DateFormatter $date_formatter, RequestStack $request_stack, BraintreeCashierService $braintree_cashier_service, BillableUser $billable_user, SubscriptionService $subscriptionService, Renderer $renderer, LoggerChannelInterface $logger) {
    $this->braintreeApi = $braintree_api_braintree_api;
    $this->userStorage = $this->entityTypeManager()->getStorage('user');
    $this->dateFormatter = $date_formatter;
    $this->requestStack = $request_stack;
    $this->config = $this->config('braintree_cashier.settings');
    $this->bcService = $braintree_cashier_service;
    $this->billableUser = $billable_user;

    // Setup Money.
    $currencies = new ISOCurrencies();
    $this->moneyParser = new DecimalMoneyParser($currencies);
    $numberFormatter = new \NumberFormatter($this->bcService->getLocale(), \NumberFormatter::CURRENCY);
    $this->moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

    $this->subscriptionService = $subscriptionService;
    $this->renderer = $renderer;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_api.braintree_api'),
      $container->get('date.formatter'),
      $container->get('request_stack'),
      $container->get('braintree_cashier.braintree_cashier_service'),
      $container->get('braintree_cashier.billable_user'),
      $container->get('braintree_cashier.subscription_service'),
      $container->get('renderer'),
      $container->get('logger.channel.braintree_cashier')
    );
  }

  /**
   * Access control handler for this route.
   *
   * @param \Drupal\Core\Session\AccountInterface $browsing_account
   *   The browsing account.
   * @param \Drupal\user\Entity\User|null $user
   *   The account being viewed.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function accessRoute(AccountInterface $browsing_account, User $user = NULL) {
    $is_allowed = $browsing_account->isAuthenticated() && !empty($user) && ($browsing_account->id() == $user->id() || $browsing_account->hasPermission('administer braintree cashier'));
    $is_allowed = $is_allowed && !empty($this->billableUser->getBraintreeCustomerId($user));
    return AccessResultAllowed::allowedIf($is_allowed);
  }

  /**
   * Invoices for a user.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user account being viewed.
   *
   * @return array
   *   Return Hello render array.
   */
  public function invoices(User $user) {

    $build = [
      '#theme' => 'invoices',
    ];

    try {
      $braintree_customer = $this->billableUser->asBraintreeCustomer($user);
    }
    catch (\Exception $e) {
      $this->messenger->addError($e->getMessage());
      $this->logger->error($e->getMessage());
      return $build;
    }

    // Build upcoming invoice.
    $rows = [];
    $header = [$this->t('Date'), $this->t('Charge')];

    $subscriptions = $this->billableUser->getSubscriptions($user);
    foreach ($subscriptions as $subscription) {
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription */
      if (!$subscription->willCancelAtPeriodEnd()) {
        $braintree_subscription = $this->subscriptionService->asBraintreeSubscription($subscription);
        $amount = $this->moneyParser->parse($braintree_subscription->nextBillingPeriodAmount, $this->config->get('currency_code'));
        $rows[] = [
          $this->dateFormatter->format($braintree_subscription->nextBillingDate->getTimestamp()),
          $this->moneyFormatter->format($amount),
        ];
      }
    }

    $build['#upcoming_invoice'] = [
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => [
        'class' => ['upcoming-invoice'],
      ],
      '#rows' => $rows,
      '#empty' => $this->t('There are no upcoming charges.'),
    ];

    $build['#billing_information_form'] = $this->formBuilder()->getForm('\Drupal\braintree_cashier\Form\InvoiceBillingInformationForm', $user);

    // Create payment history.
    /* @var \Braintree\ResourceCollection transactionSuccessfulCollection */
    $this->transactionSuccessfulCollection = $this->braintreeApi->getGateway()->transaction()->search([
      \Braintree_TransactionSearch::customerId()->is($braintree_customer->id),
      \Braintree_TransactionSearch::status()->in($this->bcService->getTransactionCompletedStatuses()),
    ]);

    $rows = [];
    $header = [$this->t('Date'), $this->t('Amount'), $this->t('Details')];

    if (!empty($this->transactionSuccessfulCollection->getIds())) {
      foreach ($this->transactionSuccessfulCollection as $transaction) {
        // Show refunds as negative amounts.
        $amount = $this->moneyParser->parse($transaction->amount, $this->config->get('currency_code'));

        $details = [
          '#theme' => 'item_list',
          '#type' => 'ul',
          '#items' => [
            [
              '#type' => 'link',
              '#title' => $this->t('View'),
              '#url' => Url::fromRoute('braintree_cashier.single_invoice_view', [
                'user' => $user->id(),
                'invoice' => $transaction->id,
              ]),
            ],
            [
              '#type' => 'link',
              '#title' => $this->t('Download'),
              '#url' => Url::fromRoute('braintree_cashier.single_invoice_download', [
                'user' => $user->id(),
                'invoice' => $transaction->id,
              ]),
            ],
          ],

        ];

        $amount_prefix = $transaction->type == \Braintree_Transaction::SALE ? '' : '-';
        $rows[] = [
          $this->dateFormatter->format($transaction->createdAt->getTimestamp()),
          $amount_prefix . $this->moneyFormatter->format($amount),
          ['data' => $details],
        ];
      }
    }

    $build['#payment_history'] = [
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => [
        'class' => ['payment-history'],
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No payments have been made.'),
    ];

    return $build;
  }

}
