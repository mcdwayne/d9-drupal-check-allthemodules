<?php

namespace Drupal\stripe_webhooks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Drupal\stripe_webhooks\Event\AccountEvent;
use Drupal\stripe_webhooks\Event\BalanceEvent;
use Drupal\stripe_webhooks\Event\BitcoinEvent;
use Drupal\stripe_webhooks\Event\ChargeEvent;
use Drupal\stripe_webhooks\Event\CouponEvent;
use Drupal\stripe_webhooks\Event\CustomerEvent;
use Drupal\stripe_webhooks\Event\DiscountEvent;
use Drupal\stripe_webhooks\Event\DisputeEvent;
use Drupal\stripe_webhooks\Event\ExternalAccountEvent;
use Drupal\stripe_webhooks\Event\InvoiceEvent;
use Drupal\stripe_webhooks\Event\InvoiceItemEvent;
use Drupal\stripe_webhooks\Event\OrderEvent;
use Drupal\stripe_webhooks\Event\OrderReturnEvent;
use Drupal\stripe_webhooks\Event\PayoutEvent;
use Drupal\stripe_webhooks\Event\PlanEvent;
use Drupal\stripe_webhooks\Event\ProductEvent;
use Drupal\stripe_webhooks\Event\RecipientEvent;
use Drupal\stripe_webhooks\Event\RefundEvent;
use Drupal\stripe_webhooks\Event\CustomerSourceEvent;
use Drupal\stripe_webhooks\Event\ReviewEvent;
use Drupal\stripe_webhooks\Event\SkuEvent;
use Drupal\stripe_webhooks\Event\SourceEvent;
use Drupal\stripe_webhooks\Event\SourceTransactionEvent;
use Drupal\stripe_webhooks\Event\SubscriptionEvent;
use Drupal\stripe_webhooks\Event\TransferEvent;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for Stripe Webhooks.
 */
class StripeWebhooksEndPoint extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The event_dispatcher object.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Stripe Webhooks logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  private $settings;

  /**
   * StripeWebhooksEndPoint constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger_channel
   *   Stripe Webhooks logging channel.
   * @param \Drupal\Core\Site\Settings $settings
   * The settings object.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   */
  public function __construct(LoggerChannelInterface $logger_channel, Settings $settings, EventDispatcherInterface $event_dispatcher) {
    $this->logger = $logger_channel;
    $this->settings = $settings;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('stripe_webhooks'),
      $container->get('settings'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Captures webhook notification.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function captureNotification(Request $request) {
    try {
      // Get the Stripe Webhooks API key and the Signing secret key.
      $api_key = $this->settings->get('stripe_webhooks_api_key');
      $signing_secret_key = $this->settings->get('stripe_webhooks_signing_secret_key');

      if (empty($api_key) || empty($signing_secret_key)) {
        $this->logger->debug("You must add your 'Secret key' API key and your 'Signing secret' webhook key to your settings.php.");

        return new Response(NULL, Response::HTTP_CONFLICT);
      }

      // Set the API key to be used.
      Stripe::setApiKey($api_key);

      // Capture the event.
      $payload = $request->getContent();
      $signature = $request->server->get('HTTP_STRIPE_SIGNATURE');

      $notification = Webhook::constructEvent($payload, $signature, $signing_secret_key);

      // Dispatch the event.
      if (strpos($notification->type, 'balance.') === 0) {
        $event = new BalanceEvent($notification);
      }
      elseif (strpos($notification->type, 'bitcoin.receiver.') === 0) {
        $event = new BitcoinEvent($notification);
      }
      elseif (strpos($notification->type, 'account.external_account.') === 0) {
        $event = new ExternalAccountEvent($notification);
      }
      elseif (strpos($notification->type, 'account.') === 0) {
        $event = new AccountEvent($notification);
      }
      elseif (strpos($notification->type, 'charge.dispute.') === 0) {
        $event = new DisputeEvent($notification);
      }
      elseif (strpos($notification->type, 'charge.refund.') === 0) {
        $event = new RefundEvent($notification);
      }
      elseif (strpos($notification->type, 'charge.') === 0) {
        $event = new ChargeEvent($notification);
      }
      elseif (strpos($notification->type, 'coupon.') === 0) {
        $event = new CouponEvent($notification);
      }
      elseif (strpos($notification->type, 'customer.discount.') === 0) {
        $event = new DiscountEvent($notification);
      }
      elseif (strpos($notification->type, 'customer.source.') === 0) {
        $event = new CustomerSourceEvent($notification);
      }
      elseif (strpos($notification->type, 'customer.subscription.') === 0) {
        $event = new SubscriptionEvent($notification);
      }
      elseif (strpos($notification->type, 'customer.') === 0) {
        $event = new CustomerEvent($notification);
      }
      elseif (strpos($notification->type, 'invoice.') === 0) {
        $event = new InvoiceEvent($notification);
      }
      elseif (strpos($notification->type, 'invoiceitem.') === 0) {
        $event = new InvoiceItemEvent($notification);
      }
      elseif (strpos($notification->type, 'order.') === 0) {
        $event = new OrderEvent($notification);
      }
      elseif (strpos($notification->type, 'order_return.') === 0) {
        $event = new OrderReturnEvent($notification);
      }
      elseif (strpos($notification->type, 'payout.') === 0) {
        $event = new PayoutEvent($notification);
      }
      elseif (strpos($notification->type, 'plan.') === 0) {
        $event = new PlanEvent($notification);
      }
      elseif (strpos($notification->type, 'product.') === 0) {
        $event = new ProductEvent($notification);
      }
      elseif (strpos($notification->type, 'recipient.') === 0) {
        $event = new RecipientEvent($notification);
      }
      elseif (strpos($notification->type, 'review.') === 0) {
        $event = new ReviewEvent($notification);
      }
      elseif (strpos($notification->type, 'sku.') === 0) {
        $event = new SkuEvent($notification);
      }
      elseif (strpos($notification->type, 'source.transaction.') === 0) {
        $event = new SourceTransactionEvent($notification);
      }
      elseif (strpos($notification->type, 'source.') === 0) {
        $event = new SourceEvent($notification);
      }
      elseif (strpos($notification->type, 'trasnfer.') === 0) {
        $event = new TransferEvent($notification);
      }
      else {
        throw new \Exception(t('Unknown event type: @type.', ['@type' => $notification->type]));
      }

      $this->logger->debug('Received event ID: @id of type: @type.', ['@id' => $notification->id, '@type' => $notification->type]);
      $this->eventDispatcher->dispatch('stripe.webhooks.' . $notification->type, $event);
    }
    catch (\Exception $e) {
      watchdog_exception('stripe_webhooks', $e);

      return new Response(NULL, Response::HTTP_BAD_REQUEST);
    }

    return new Response(NULL, Response::HTTP_OK);
  }

}
