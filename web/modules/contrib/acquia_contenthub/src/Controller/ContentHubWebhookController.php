<?php

namespace Drupal\acquia_contenthub\Controller;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\acquia_contenthub\Event\HandleWebhookEvent;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for ContentHub webhooks.
 */
class ContentHubWebhookController extends ControllerBase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Content Hub Client Factory.
   *
   * @var \Drupal\acquia_contenthub\Client\ClientFactory
   */
  protected $clientFactory;

  /**
   * WebhooksSettingsForm constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\acquia_contenthub\Client\ClientFactory $client_factory
   *   The client factory.
   */
  public function __construct(EventDispatcherInterface $dispatcher, ClientFactory $client_factory) {
    $this->dispatcher = $dispatcher;
    $this->clientFactory = $client_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('acquia_contenthub.client.factory')
    );
  }

  /**
   * Process an incoming webhook from the ContentHub Service.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A Symfony request object.
   *
   * @return mixed
   *   The response.
   */
  public function receiveWebhook(Request $request) {
    // If you're using a host re-writer, you need to find the original host.
    if ($request->headers->has('x-original-host')) {
      $request->headers->set('host', $request->headers->get('x-original-host'));
    }
    // Obtain the headers.
    $payload = $request->getContent();

    $key = $this->validateWebhookSignature($request, $payload);
    if ($key) {
      // Notify about the arrival of the webhook request.
      $this->getLogger('acquia_contenthub')->debug(new FormattableMarkup('Webhook landing: @whook', ['@whook' => print_r($payload, TRUE)]));

      if ($payload = Json::decode($payload)) {
        $event = new HandleWebhookEvent($request, $payload, $key, $this->clientFactory->getClient());
        $this->dispatcher->dispatch(AcquiaContentHubEvents::HANDLE_WEBHOOK, $event);
        return $event->getResponse();
      }
    }
    else {
      $ip_address = $request->getClientIp();
      $message = new FormattableMarkup('Webhook [from IP = @IP] rejected (Signatures do not match): @whook', [
        '@IP' => $ip_address,
        '@whook' => print_r($payload, TRUE),
      ]);
      $this->getLogger('acquia_contenthub')->debug($message);
    }

    return new Response();
  }

  /**
   * Validates a webhook signature.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $payload
   *   The request payload.
   *
   * @return bool|\Acquia\Hmac\KeyInterface
   *   TRUE if signature verification passes, FALSE otherwise.
   */
  public function validateWebhookSignature(Request $request, $payload) {
    return $this->clientFactory->authenticate($request);
  }

}
