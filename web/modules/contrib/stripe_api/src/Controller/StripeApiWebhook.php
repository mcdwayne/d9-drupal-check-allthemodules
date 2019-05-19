<?php

namespace Drupal\stripe_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\stripe_api\Event\StripeApiWebhookEvent;
use Drupal\stripe_api\StripeApiService;
use Stripe\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class StripeApiWebhook.
 *
 * Provides the route functionality for stripe_api.webhook route.
 */
class StripeApiWebhook extends ControllerBase {

  // Fake ID from Stripe we can check against.
  const FAKE_EVENT_ID = 'evt_00000000000000';

  /**
   * @var \Drupal\stripe_api\StripeApiService*/
  protected $stripeApi;

  /**
   * {@inheritdoc}
   */
  public function __construct(StripeApiService $stripe_api) {
    $this->stripeApi = $stripe_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('stripe_api.stripe_api')
    );
  }

  /**
   * Captures the incoming webhook request.
   *
   * @param $request Request
   *
   * @return Response
   */
  public function handleIncomingWebhook(Request $request) {
    $input = $request->getContent();
    $decoded_input = json_decode($input);
    $config = $this->config('stripe_api.settings');
    $mode = $config->get('mode') ?: 'test';

    if (!$event = $this->isValidWebhook($mode, $decoded_input)) {
      $this->getLogger('stripe_api')
        ->error('Invalid webhook event: @data', [
          '@data' => $input,
        ]);
      return new Response(NULL, Response::HTTP_FORBIDDEN);
    }

    /** @var LoggerChannelInterface $logger */
    $logger = $this->getLogger('stripe_api');
    $logger->info("Stripe webhook received event:\n @event", ['@event' => (string) $event]);

    // Dispatch the webhook event.
    $dispatcher = \Drupal::service('event_dispatcher');
    $e = new StripeApiWebhookEvent($event->type, $decoded_input->data, $event);
    $dispatcher->dispatch('stripe_api.webhook', $e);

    return new Response('Okay', Response::HTTP_OK);
  }

  /**
   * Determines if a webhook is valid.
   *
   * @param string $mode
   *   Stripe API mode. Either 'live' or 'test'.
   * @param object $event_json
   *   Stripe event object parsed from JSON.
   *
   * @return bool|\Stripe\Event
   *   Returns TRUE if the webhook is valid or the Stripe Event object.
   */
  private function isValidWebhook($mode, $data) {
    if (!empty($data->id)) {

      if ($mode === 'live' && $data->livemode == TRUE
      || $mode === 'test' && $data->livemode == FALSE
      || $data->id == self::FAKE_EVENT_ID) {

        // Verify the event by fetching it from Stripe.
        return Event::retrieve($data->id);
      }
    }

    return FALSE;
  }

}
