<?php

namespace Drupal\smartwaiver\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use Drupal\smartwaiver\Event\SmartwaiverEvent;
use Drupal\smartwaiver\ClientInterface;

class DefaultController extends ControllerBase {

  /**
   * The smartwaiver client api service.
   *
   * @var \Drupal\smartwaiver\ClientInterface;
   */
  protected $client;

  /**
   * The immutable smartwaiver config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The immutable smartwaiver config object.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  public function __construct(ClientInterface $smartwaiver_client, ConfigFactoryInterface $config_factory, EventDispatcherInterface $event_dispatcher) {
    $this->client = $smartwaiver_client;
    $this->config = $config_factory->get('smartwaiver.config');
    $this->eventDispatcher = $event_dispatcher;
    $this->logger = $this->getLogger('smartwaiver');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('smartwaiver.client'),
      $container->get('config.factory'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Handles an incoming webhook request and fires off an event so other modules
   * can react to it.
   */
  public function webhook(Request $request) {
    $unique_id = $request->request->get('unique_id');

    if ($problem = $this->invalid($request)) {
      return new JsonResponse(['message' => $problem], 400);
    }

    try {
      $this->process($unique_id);
    } catch (\Exception $e) {
      $this->log('Failed to process webhook with unique_id: @unique_id with exception @e', [
        '@unique_id' => $unique_id,
        '@e' => $e->getMessage(),
      ]);
      return new JsonResponse(['message' => 'Failed to process webhook.'], 500);
    }

    $this->log('Webhook received with unique_id: @unique_id', [
      '@unique_id' => $unique_id,
    ]);

    return new JsonResponse(['message' => 'Webhook received.']);
  }

  public function process($unique_id) {
    $waiver = $this->client->waiver($unique_id);

    if ($waiver && $this->isActiveWaiver($waiver)) {
      $event = $this->newEvent($waiver);
      $this->eventDispatcher->dispatch(SmartwaiverEvent::NEW_WAIVER, $event);
    }
  }

  public function invalid(Request $request) {
    $parameters = $this->getParameters($request);

    // Validate the request has all needed parameters.
    $required_params = [
      'unique_id',
      'credential',
      'event',
    ];

    if (!$has_all_params = $this->hasAllParams($request, $required_params)) {
      $missing = array_diff($required_params, $request->request->keys());
      $message = $this->t("Invalid request. Missing expected parameters: @params", [
        '@params' => join('\'', array_values($missing)),
      ]);
      $this->log($message);
      return $message;
    }

    if(!$is_new_waiver = $this->requestEventIs($request, 'new-waiver')) {
      $message = $this->t("Invalid request. Unexpected event type: @type", [
        '@type' => $request->request->get('event'),
      ]);
      $this->log($message);
      return $message;
    }

    return FALSE;
  }

  protected function newEvent(\StdClass $waiver) {
    return new SmartwaiverEvent($waiver);
  }

  protected function getActiveWaivers() {
    $waiver_type_guids = $this->config->get('enabled_waivers');
    return array_filter($waiver_type_guids);
  }

  protected function isActiveWaiver($waiver) {
    $active_waivers = $this->getActiveWaivers();
    return
      isset($waiver->templateId)
      && in_array($waiver->templateId, $active_waivers);
  }

  protected function getParameters(Request $request) {
    return $request->request;
  }

  protected function getQuery(Request $request) {
    return $request->query;
  }

  protected function hasAllParams(Request $request, array $params) {
    $parameters = $this->getParameters($request);
    return array_reduce($params, function ($has_all, $param) use ($parameters) {
      return (!$has_all) ? FALSE : $parameters->has($param);
    }, TRUE);
  }

  protected function requestEventIs($request, $event) {
    return $this->getParameters($request)->get('event') == $event;
  }

  protected function log($message, $context = []) {
    $this->logger->info($message, $context);
  }

}
