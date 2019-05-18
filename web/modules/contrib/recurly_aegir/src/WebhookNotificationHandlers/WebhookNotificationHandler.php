<?php

namespace Drupal\recurly_aegir\WebhookNotificationHandlers;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\recurly_aegir\Wrappers\SubscriptionWrapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes Recurly notifications for Aegir.
 */
abstract class WebhookNotificationHandler {

  const NOTIFICATION_MAP = [
    'new_account_notification' => 'NewAccountWebhookNotificationHandler',
    'canceled_account_notification' => 'CanceledAccountWebhookNotificationHandler',
    'billing_info_updated_notification' => 'UpdatedAccountWebhookNotificationHandler',
    'reactivated_account_notification' => 'ReactivatedAccountWebhookNotificationHandler',
    'new_invoice_notification' => 'NewInvoiceWebhookNotificationHandler',
    'processing_invoice_notification' => 'ProcessingInvoiceWebhookNotificationHandler',
    'closed_invoice_notification' => 'ClosedInvoiceWebhookNotificationHandler',
    'past_due_invoice_notification' => 'OverdueInvoiceWebhookNotificationHandler',
    'new_subscription_notification' => 'NewSubscriptionWebhookNotificationHandler',
    'updated_subscription_notification' => 'UpdatedSubscriptionWebhookNotificationHandler',
    'canceled_subscription_notification' => 'CanceledSubscriptionWebhookNotificationHandler',
    'expired_subscription_notification' => 'ExpiredSubscriptionWebhookNotificationHandler',
    'renewed_subscription_notification' => 'RenewedSubscriptionWebhookNotificationHandler',
    'scheduled_payment_notification' => 'ScheduledPaymentWebhookNotificationHandler',
    'processing_payment_notification' => 'ProcessingPaymentWebhookNotificationHandler',
    'successful_payment_notification' => 'SuccessfulPaymentWebhookNotificationHandler',
    'failed_payment_notification' => 'FailedPaymentWebhookNotificationHandler',
    'successful_refund_notification' => 'SuccessfulRefundPaymentWebhookNotificationHandler',
    'void_payment_notification' => 'VoidPaymentWebhookNotificationHandler',
  ];

  /**
   * Account information.
   *
   * @var SimpleXMLElement|null
   */
  protected $account;

  /**
   * Subscription information.
   *
   * @var Drupal\recurly_aegir\Wrappers\SubscriptionWrapper|null
   */
  protected $subscription;

  /**
   * Transaction information.
   *
   * @var SimpleXMLElement|null
   */
  protected $transaction;

  /**
   * Invoice information.
   *
   * @var SimpleXMLElement|null
   */
  protected $invoice;

  protected $result = FALSE;

  /**
   * The logging service.
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The current HTTP/S request.
   *
   * @var Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The module handler.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class. The
   * factory should pass any needed dependencies into the constructor of this
   * class, but not the container itself. Every call to this method must return
   * a new instance of this class; that is, it may not implement a singleton.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   * @param array $data
   *   Notification data.
   *
   * @see ContainerInjectionInterface::create()
   */
  public static function create(ContainerInterface $container, array $data) {
    return new static(
      $data,
      $container->get('logger.factory')->get('recurly_aegir'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('module_handler')
    );
  }

  /**
   * Class constuctor.
   *
   * @param array $data
   *   The notification data.
   * @param Psr\Log\LoggerInterface $logger
   *   Invoice information.
   * @param Symfony\Component\HttpFoundation\Request $current_request
   *   The current HTTP/S request.
   * @param Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $data, LoggerInterface $logger, Request $current_request, ModuleHandlerInterface $module_handler) {
    $this->account = $data['account'];
    $this->transaction = $data['transaction'];
    $this->invoice = $data['invoice'];
    $this->logger = $logger;
    $this->currentRequest = $current_request;
    $this->moduleHandler = $module_handler;

    if (empty($data['subscription']->uuid)) {
      $this->subscription = NULL;
    }
    else {
      $this->subscription = SubscriptionWrapper::get((string) $data['subscription']->uuid);
    }
  }

  /**
   * Handle a webhook notification from Recurly.
   *
   * Must be overriden by subclasses to actually do something.
   *
   * @return $this
   *   The object itself.
   */
  abstract public function handleNotification();

  /**
   * Get the processing result.
   *
   * @return bool
   *   TRUE if processing was successful; FALSE otherwise.
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Fetch the remote account code.
   *
   * @return string
   *   The remote account code.
   */
  protected function getAccountCode() {
    return (string) $this->account->account_code;
  }

  /**
   * Log a warning message and return the current object.
   */
  protected function failAndLogMessage($message) {
    $this->logger->warning($message, [
      '%uuid' => $this->subscription->getUuid(),
      '%user' => $this->subscription->getLocalUserId($this->getAccountCode()),
    ]);

    $this->result = FALSE;
    return $this;
  }

}
