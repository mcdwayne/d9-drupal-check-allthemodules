<?php

/**
 * @file
 * Contains \Drupal\alexa\Controller\AlexaEndpointController.
 *
 * This is the Alexa endpoint controller that will receive an event on
 * https://example.com/alexa/callback and then will:
 * 1. Validate the request as genuine
 * 2. Dispatch a Symfony event to let anyone to respond to the request, allowing
 *    modules to easily create new Alexa Skills without having to worry about
 *    request validation and routing.
 */

namespace Drupal\alexa\Controller;

use Alexa\Response\Response as AlexaResponse;
use Alexa\Request\Request as AlexaRequest;
use Alexa\Request\Certificate;
use Drupal\alexa\AlexaEvent;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The controller that will respond to requests on the Alexa callback endpoint.
 */
class AlexaEndpointController extends ControllerBase {

  /**
   * The Symfony event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * AlexaEndpointController constructor.
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
   * The endpoint callback function for handling Alexa requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP Alexa request that was received.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The Alexa response in JSON format.
   */
  public function callback(Request $request) {
    $content = $request->getContent();
    if (!empty($content)) {
      try {
        // Use our own version of the Certificate class so we can implement
        // Drupal caching of a downloaded certificate.
        $certificate = new AlexaCachedCertificate(
          $request->headers->get('signaturecertchainurl'),
          $request->headers->get('signature'),
          $this->cache()
        );

        $config = $this->config('alexa.settings');

        $alexa = new AlexaRequest($content, $config->get('application_id'));
        $alexa->setCertificateDependency($certificate);

        // Parse and validate the request.
        $alexaRequest = $alexa->fromData();

        $response = new AlexaResponse();

        $event = new AlexaEvent($alexaRequest, $response);
        $this->eventDispatcher->dispatch('alexaevent.request', $event);

        return new JsonResponse($response->render());
      }
      catch (\InvalidArgumentException $e) {
        watchdog_exception('alexa', $e);
      }
    }

    return new JsonResponse(NULL, 500);
  }

}

/**
 * Overloads the default Amazon Alexa App library Certificate class.
 *
 * Overload the default Amazon Alexa App library Certificate class to allow
 * Drupal-based caching of the downloaded Amazon certificate.
 */
class AlexaCachedCertificate extends Certificate {

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheService;

  /**
   * AlexaCachedCertificate constructor.
   *
   * @param string $certificateUrl
   *   The Alexa certificate URL.
   * @param string $signature
   *   The Alexa request signature.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheService
   *   The cache service to use.
   */
  public function __construct($certificateUrl, $signature, CacheBackendInterface $cacheService) {
    parent::__construct($certificateUrl, $signature);

    $this->cacheService = $cacheService;
  }

  /**
   * {@inheritdoc}
   */
  public function getCertificate() {
    $cid = 'alexa:certificate:' . $this->certificateUrl;
    $certificate = NULL;
    if ($cache = $this->cacheService->get($cid)) {
      $certificate = $cache->data;
    }
    else {
      $response = \Drupal::httpClient()->get($this->certificateUrl);
      $certificate = (string) $response->getBody();
      $this->cacheService->set($cid, $certificate);
    }
    return $certificate;
  }

  /**
   * {@inheritdoc}
   */
  public function validateRequest($requestData) {
    if (!\Drupal::state()->get('alexa.dev_mode', FALSE)) {
      parent::validateRequest($requestData);
    }
  }

}
