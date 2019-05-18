<?php

namespace Drupal\api_ai_webhook\Controller;

use DialogFlow\Model\Webhook\Response as WebhookResponse;
use DialogFlow\Model\Webhook\Request as WebhookRequest;
use Drupal\api_ai_webhook\ApiAiEvent;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The controller that will respond to requests on the Alexa callback endpoint.
 *
 * This is the Api.ai webhook endpoint controller that will receive an event on
 * https://example.com/api.ai/webhook and then will:
 * 1. Validate the request Authentication
 * 2. Dispatch a Symfony event to let anyone to respond to the request, allowing
 *    modules to easily create new response without having to worry about
 *    request validation and routing.
 */
class ApiAiEndpointController extends ControllerBase {

  /**
   * The Symfony event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * ApiAiEndpointController constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The Symfony event dispatcher to use.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('event_dispatcher'));
  }

  /**
   * The endpoint callback function for handling requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $http_request
   *   The HTTP request that was received.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response in JSON format.
   */
  public function callback(Request $http_request) {
    $content = $http_request->getContent();
    if (!empty($content)) {
      try {

        $request = new WebhookRequest(json_decode($content, TRUE));
        $response = new WebhookResponse([], $request->getSession());

        $event = new ApiAiEvent($request, $response);
        $this->eventDispatcher->dispatch($event::NAME, $event);

        return new JsonResponse($response->jsonSerialize());
      }
      catch (\InvalidArgumentException $e) {
        watchdog_exception('api_ai_webhook', $e);
      }
    }

    return new JsonResponse(NULL, 500);
  }

}
