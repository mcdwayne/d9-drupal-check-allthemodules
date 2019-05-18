<?php

namespace Drupal\braintree_api\Controller;

use Drupal\braintree_api\Event\BraintreeApiEvents;
use Drupal\braintree_api\Event\BraintreeApiWebhookEvent;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\braintree_api\BraintreeApiService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BraintreeApiWebhook.
 */
class BraintreeApiWebhook extends ControllerBase {

  /**
   * Drupal\braintree_api\BraintreeApiService definition.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * The Braintree API Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new BraintreeApiWebhook object.
   */
  public function __construct(BraintreeApiService $braintree_api, LoggerChannel $logger, EventDispatcherInterface $event_dispatcher) {
    $this->braintreeApi = $braintree_api;
    $this->logger = $logger;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_api.braintree_api'),
      $container->get('logger.channel.braintree_api'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Handle incoming webhook.
   *
   * @return string
   *   Return Hello string.
   */
  public function handleIncomingWebhook() {
    if (isset($_POST["bt_signature"]) && isset($_POST["bt_payload"])) {
      try {
        $webhook_notification = $this->braintreeApi->getGateway()->webhookNotification()->parse(
          trim($_POST["bt_signature"]), trim($_POST["bt_payload"])
        );
      }
      catch (\Exception $e) {
        $this->logger->error('Error parsing the Braintree Webhook: ' . $e->getMessage() . '(' . get_class($e) . ')  ' . print_r($_POST["bt_signature"], TRUE) . '  ' . print_r($_POST["bt_payload"], TRUE));
        return new Response('Forbidden', Response::HTTP_FORBIDDEN);

      }

      // Dispatch the webhook event.
      $event = new BraintreeApiWebhookEvent($webhook_notification->kind, $webhook_notification);
      $this->eventDispatcher->dispatch(BraintreeApiEvents::WEBHOOK, $event);

      return new Response('Thanks!', Response::HTTP_OK);
    }
    return new Response(NULL, Response::HTTP_FORBIDDEN);
  }

}
