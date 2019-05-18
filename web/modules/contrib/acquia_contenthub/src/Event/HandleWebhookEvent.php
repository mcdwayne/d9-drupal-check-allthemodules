<?php

namespace Drupal\acquia_contenthub\Event;

use Acquia\ContentHubClient\ContentHubClient;
use Acquia\Hmac\KeyInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An event for responding to incoming webhooks from the ContentHub Service.
 *
 * Drupal responds to routes at the controller level, but many different
 * webhooks can be sent by the ContentHub Service, and they all come across the
 * same controller class. In order to allow multiple different actors to react
 * on incoming webhooks, this event is passed around with the payload of the
 * incoming request.
 *
 * This event only executes when the HMAC header is successfully validated.
 *
 * @see \Drupal\acquia_contenthub\Controller\ContentHubWebhookController::receiveWebhook
 */
class HandleWebhookEvent extends Event {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The request payload.
   *
   * @var array
   */
  protected $payload;

  /**
   * The Key interface used to validate the request.
   *
   * @var \Acquia\Hmac\KeyInterface
   */
  protected $key;

  /**
   * The ContentHub Client.
   *
   * @var \Acquia\ContentHubClient\ContentHubClient
   */
  protected $client;

  /**
   * The response to send back to the ContentHub service.
   *
   * @var mixed
   */
  protected $response;

  /**
   * HandleWebhookEvent constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param array $payload
   *   The payload.
   * @param \Acquia\Hmac\KeyInterface $key
   *   The key.
   * @param \Acquia\ContentHubClient\ContentHubClient $client
   *   The Content Hub client.
   */
  public function __construct(Request $request, array $payload, KeyInterface $key, ContentHubClient $client) {
    $this->request = $request;
    $this->payload = $payload;
    $this->key = $key;
    $this->client = $client;
  }

  /**
   * Get the incoming webhook request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request.
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Get the payload of the incoming webhook request.
   *
   * @return array
   *   The payload.
   */
  public function getPayload() {
    return $this->payload;
  }

  /**
   * Get the key that was used to validate the request.
   *
   * @return \Acquia\Hmac\KeyInterface
   *   The key.
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * Sets the response to return for this webhook.
   *
   * Drupal can handle many different response types which is why this method
   * parameter is not type hinted.
   *
   * @param mixed $response
   *   The response for this webhook.
   */
  public function setResponse($response) {
    $this->response = $response;
  }

  /**
   * Whether a response has been set for this webhook.
   *
   * @return bool
   *   TRUE if has response; FALSE otherwise.
   */
  public function hasResponse() {
    return (bool) $this->response;
  }

  /**
   * Get the response for this webhook.
   *
   * @return mixed
   *   The response.
   */
  public function getResponse() {
    if ($this->hasResponse()) {
      return $this->response;
    }
    return new Response('');
  }

  /**
   * Get the ContentHub Client.
   *
   * @return \Acquia\ContentHubClient\ContentHubClient
   *   Content Hub client.
   */
  public function getClient() {
    return $this->client;
  }

}
