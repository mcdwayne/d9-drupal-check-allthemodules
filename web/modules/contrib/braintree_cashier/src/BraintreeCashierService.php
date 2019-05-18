<?php

namespace Drupal\braintree_cashier;

use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface;
use Drupal\braintree_cashier\Entity\BraintreeCashierDiscount;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Helper.
 */
class BraintreeCashierService {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */

  protected $mailManager;


  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Billing plan storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $billingPlanStorage;

  /**
   * The Braintree API service.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * The braintree_cashier logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The discount entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $discountStorage;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new Helper object.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The user entity.
   * @param \Drupal\Core\Mail\MailManagerInterface $plugin_manager_mail
   *   The mail manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\braintree_api\BraintreeApiService $braintree_api
   *   The Braintree API service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The braintree_cashier logger channel.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(AccountProxy $current_user, MailManagerInterface $plugin_manager_mail, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, BraintreeApiService $braintree_api, LoggerChannelInterface $logger, RequestStack $requestStack, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->mailManager = $plugin_manager_mail;
    $this->configFactory = $config_factory;
    $this->billingPlanStorage = $entity_type_manager->getStorage('braintree_cashier_billing_plan');
    $this->discountStorage = $entity_type_manager->getStorage('braintree_cashier_discount');
    $this->braintreeApi = $braintree_api;
    $this->logger = $logger;
    $this->requestStack = $requestStack;
    $this->messenger = $messenger;
  }

  /**
   * Sends an error email to the site administrator.
   *
   * @param string $message
   *   The error message to send to the site administrator.
   */
  public function sendAdminErrorEmail($message) {
    $to = $this->configFactory->get('system.site')->get('mail');
    $lang_code = $this->configFactory->get('system.site')->get('langcode');
    $this->mailManager->mail('braintree_cashier', 'admin_error', $to, $lang_code, ['message' => $message]);
  }

  /**
   * Loads a billing plan entity from a Braintree Plan Id.
   *
   * @param string $braintree_plan_id
   *   The ID of the plan displayed in the Braintree control panel.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface|false
   *   The billing plan entity.
   */
  public function getBillingPlanFromBraintreePlanId($braintree_plan_id) {
    $query = $this->billingPlanStorage->getQuery();
    // It's safe to limit the range to 1 since the validation API ensures that
    // only one billing plan of a given type can be created for each Braintree
    // Plan ID.
    $query->condition('environment', $this->braintreeApi->getEnvironment())
      ->condition('braintree_plan_id', $braintree_plan_id)
      ->range(0, 1);
    $result = $query->execute();
    if (!empty($result)) {
      $entity_id = array_shift($result);
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan */
      $billing_plan = $this->billingPlanStorage->load($entity_id);
      return $billing_plan;
    }
    return FALSE;
  }

  /**
   * Handles a Braintree transaction which has a status of "processor_declined".
   *
   * @see https://developers.braintreepayments.com/reference/response/transaction/php#processor-declined
   */
  public function handleProcessorDeclined($processor_response_code, $processor_response_text) {
    $this->logger->error('Processor declined the transaction. %code:%message', [
      '%code' => $processor_response_code,
      '%message' => $processor_response_text,
    ]);
    switch ($processor_response_code) {
      case 2010:
        // Card Issuer Declined CVV.
        $this->messenger->addError($this->t('Your bank reported that you entered in an invalid security code or made a typo in your card information. Please re-enter your card information.'));
        break;

      case 2006:
        // Invalid Expiration Date.
        $this->messenger->addError($this->t('Your bank reported that you made a typo in your card expiration date. Please re-enter your card information'));
        break;

      case 2004:
        // Expired Card.
        $this->messenger->addError($this->t('Your card has expired. Please use a different payment method.'));
        break;

      case 2024:
        // Card Type Not Enabled.
        $this->messenger->addError($this->t('Our payment processor can not use this brand of card. Please choose a different payment method.'));
        break;

      default:
        $this->messenger->addError($this->t('Card declined. Please either choose a different payment method or contact your bank to request accepting charges from this website.'));
    }
  }

  /**
   * Handles a Braintree transaction which has a status of "gateway_rejected".
   *
   * @param string $reason
   *   The reason for the rejection.
   *
   * @see https://developers.braintreepayments.com/reference/response/transaction/php#gateway-rejection
   */
  public function handleGatewayRejected($reason) {
    $this->logger->error('Gateway rejected. Reason: ' . $reason);
    $this->messenger->addError($this->t('Our payment processor rejected this transaction, and reported the following reason: %reason', [
      '%reason' => $reason,
    ]));
  }

  /**
   * Handle a "processor_settlement_declined" transaction.
   *
   * This status is rare, and only certain types of transactions can be
   * affected.
   *
   * @param \Braintree_Transaction $transaction
   *   The transaction property of the Braintree response object.
   *   $transaction->status must be "processor_settlement_declined".
   *
   * @see https://developers.braintreepayments.com/reference/response/transaction/php#processor-settlement-declined
   */
  public function handleProcessorSettlementDeclined(\Braintree_Transaction $transaction) {
    $this->logger->error('Processor declined settlement of the transaction. %code:%message', [
      '%code' => $transaction->processorSettlementResponseCode,
      '%message' => $transaction->processorSettlementResponseText,
    ]);
    $this->messenger->addError($this->t('It was not possible to create your subscription. Please contact the site administrator.'));
  }

  /**
   * Gets the Braintree Plan.
   *
   * @param string $braintree_plan_id
   *   The machine name of the Plan in the Braintree control panel.
   *
   * @return \Braintree_Plan
   *   The Braintree Plan object.
   *
   * @throws \Exception
   */
  public function getBraintreeBillingPlan($braintree_plan_id) {
    $plans = $this->braintreeApi->getGateway()->plan()->all();
    foreach ($plans as $plan) {
      if ($plan->id == $braintree_plan_id) {
        return $plan;
      }
    }
    throw new \Exception("Unable to find Braintree plan with ID [{$braintree_plan_id}].");
  }

  /**
   * Gets the Braintree discount object.
   *
   * @param string $discount_id
   *   The discount ID contained in the Braintree console.
   *
   * @return \Braintree_Discount
   *   The Braintree discount object.
   *
   * @throws \Exception
   */
  public function getBraintreeDiscount($discount_id) {
    $discounts = $this->braintreeApi->getGateway()->discount()->all();
    foreach ($discounts as $discount) {
      if ($discount->id == $discount_id) {
        return $discount;
      }
    }
    throw new \Exception("Unable to find the Braintree discount with ID {$discount_id}");
  }

  /**
   * Gets an array of transactions statuses which are considered completed.
   *
   * This is every status except those indicating a failed transaction.
   *
   * @return array
   *   An array of Braintree transaction statuses.
   */
  public function getTransactionCompletedStatuses() {
    return [
      \Braintree_Transaction::AUTHORIZING,
      \Braintree_Transaction::AUTHORIZED,
      \Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT,
      \Braintree_Transaction::SETTLING,
      \Braintree_Transaction::SETTLEMENT_PENDING,
      \Braintree_Transaction::SETTLED,
    ];
  }

  /**
   * Checks whether the provided coupon code is valid.
   *
   * Checks whether it is published and is applicable to the provided billing
   * plan entity.
   *
   * @param \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface $billing_plan
   *   The billing plan.
   * @param string $coupon_code
   *   The coupon code.
   *
   * @return bool
   *   A boolean indicating whether the provided coupon code applies to the
   *   provided billing plan.
   */
  public function discountExists(BraintreeCashierBillingPlanInterface $billing_plan, $coupon_code) {
    $query = $this->discountStorage->getQuery();

    // Check that the code exists, and applies to the current selected plan.
    // Queries are case insensitive.
    $query->condition('billing_plan', $billing_plan->id())
      ->condition('status', TRUE)
      ->condition('discount_id', $coupon_code);

    $results = $query->execute();
    foreach ($results as $result) {
      $discount = BraintreeCashierDiscount::load($result);
      // The Braintree API is case sensitive.
      if (strcmp($discount->getBraintreeDiscountId(), $coupon_code) === 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets the locale to use with the \NumberFormatter class.
   *
   * @return string
   *   The locale.
   */
  public function getLocale() {
    if ($this->configFactory->get('braintree_cashier.settings')->get('force_locale_en')) {
      return 'en';
    }
    return $this->requestStack->getCurrentRequest()->getLocale();
  }

}
