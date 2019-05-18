<?php

namespace Drupal\aegir_site_subscriptions_recurly\WebhookNotificationHandlers;

use Drupal\aegir_site_subscriptions\HostingServiceCalls\SiteDeleteHostingServiceCall;
use Drupal\aegir_site_subscriptions\Services\Site;
use Drupal\aegir_site_subscriptions\Services\Subscription;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @var \SimpleXMLElement|null
   */
  protected $account;

  /**
   * Subscription information.
   *
   * @var \Drupal\aegir_site_subscriptions\Services\Subscription|null
   */
  protected $subscription;

  /**
   * Transaction information.
   *
   * @var \SimpleXMLElement|null
   */
  protected $transaction;

  /**
   * Invoice information.
   *
   * @var \SimpleXMLElement|null
   */
  protected $invoice;

  protected $result = FALSE;

  /**
   * The logging service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The site service.
   *
   * @var \Drupal\aegir_site_subscriptions\Services\Site
   */
  protected $siteService;

  /**
   * The site deletion service.
   *
   * @var \Drupal\aegir_site_subscriptions\HostingServiceCalls\SiteDeleteHostingServiceCall
   */
  protected $siteDeletionService;

  /**
   * Factory method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   * @param array $data
   *   Notification data.
   *
   * @see ContainerInjectionInterface::create()
   *
   * @return \Drupal\aegir_site_subscriptions_recurly\WebhookNotificationHandlers\WebhookNotificationHandler
   */
  public static function create(ContainerInterface $container, array $data) {
    return new static(
      $data,
      $container->get('logger.factory')->get('aegir_site_subscriptions_recurly'),
      $container->get('aegir_site_subscriptions.site'),
      $container->get('aegir_site_subscriptions.subscription'),
      $container->get('aegir_site_subscriptions.hosting.site_deletion')
    );
  }

  /**
   * Class constuctor.
   *
   * @param array $data
   *   The notification data.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logging service.
   * @param \Drupal\aegir_site_subscriptions\Services\Site $site_service
   *   The site service.
   * @param \Drupal\aegir_site_subscriptions\Services\Subscription $subscription_service
   *   The subscription service.
   * @param \Drupal\aegir_site_subscriptions\HostingServiceCalls\SiteDeleteHostingServiceCall
   *   The site deletion service.
   */
  public function __construct(
    array $data,
    LoggerInterface $logger,
    Site $site_service,
    Subscription $subscription_service,
    SiteDeleteHostingServiceCall $site_deletion_service
  ) {
    $this->account = $data['account'];
    $this->transaction = $data['transaction'];
    $this->invoice = $data['invoice'];
    $this->logger = $logger;
    $this->siteService = $site_service;
    $this->siteDeletionService = $site_deletion_service;

    if (empty($data['subscription']->uuid)) {
      $this->subscription = NULL;
    }
    else {
      $this->subscription = $subscription_service->setSubscriptionById((string) $data['subscription']->uuid);
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
   *
   * @param $message
   *   The message to log.
   *
   * @return $this
   *   The object itself.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function failAndLogMessage($message) {
    $this->logger->warning($message, [
      '%uuid' => $this->subscription->getId(),
      '%user' => $this->subscription->getDrupalUserId($this->getAccountCode()),
    ]);

    $this->result = FALSE;
    return $this;
  }

}
