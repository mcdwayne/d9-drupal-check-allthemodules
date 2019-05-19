<?php

namespace Drupal\stripe\Controller;

use Stripe\Error\SignatureVerification;
use Stripe\Event;
use Stripe\Stripe;
use Stripe\Webhook;
use Drupal\Core\Controller\ControllerBase;
use Drupal\stripe\Event\StripeEvents;
use Drupal\stripe\Event\StripeWebhookEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for book routes.
 */
class StripeWebhookController extends ControllerBase {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Creates a new instance.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * When this controller is created,
   * it will get the di_example.talk service and store it.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher')
    );
  }

  /**
   * Handle webhook.
   */
  public function handle(Request $request) {
    $config = \Drupal::config('stripe.settings');

    Stripe::setApiKey($config->get('apikey.' . $config->get('environment') . '.secret'));

    $environment = $config->get('environment');
    $payload = @file_get_contents("php://input");
    $signature = $request->server->get('HTTP_STRIPE_SIGNATURE');
    $secret = $config->get("apikey.$environment.webhook");

    try {
      if (!empty($secret)) {
        $event = Webhook::constructEvent($payload, $signature, $secret);
      }
      else {
        $data = json_decode($payload, TRUE);
        $jsonError = json_last_error();
        if ($data === NULL && $jsonError !== JSON_ERROR_NONE) {
          $msg = "Invalid payload: $payload (json_last_error() was $jsonError)";
          throw new \UnexpectedValueException($msg);
        }

        if ($environment == 'live') {
          $event = Event::retrieve($data['id']);
        }
        else {
          $event = Event::constructFrom($data, NULL);
        }
      }
    }
    catch (\UnexpectedValueException $e) {
      return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
    }
    catch (SignatureVerification $e) {
      return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
    }

    // Dispatch the webhook event.
    $this->eventDispatcher
      ->dispatch(StripeEvents::WEBHOOK, new StripeWebhookEvent($event));

    return new Response('OK', Response::HTTP_OK);
  }

}
