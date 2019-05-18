<?php

namespace Drupal\atm\Controller;

use Drupal\atm\Helper\AtmApiHelper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides routers for controller atm.
 */
class AtmApiController extends ControllerBase {

  /**
   * Provides helper for ATM.
   *
   * @var \Drupal\atm\Helper\AtmApiHelper
   */
  private $atmApiHelper;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * Cache implementation.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * AtmApiController constructor.
   *
   * @param \Drupal\atm\Helper\AtmApiHelper $atmApiHelper
   *   Provides helper for ATM.
   * @param \GuzzleHttp\Client $httpClient
   *   Http client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache implementation.
   */
  public function __construct(AtmApiHelper $atmApiHelper, Client $httpClient, CacheBackendInterface $cache) {
    $this->atmApiHelper = $atmApiHelper;
    $this->httpClient = $httpClient;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('atm.helper'),
      $container->get('http_client'),
      $container->get('cache.default')
    );
  }

  /**
   * Redirect to atm.js.
   */
  public function getJs() {
    $jsPath = $this->atmApiHelper->get('build_path');

    $isSecure = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443);

    if ($isSecure) {
      $jsPath = preg_replace("/^http/", 'https', $jsPath);
    }

    return new RedirectResponse($jsPath);
  }

  /**
   * Return service worker js.
   */
  public function getSwJs() {
    $response = new Response('', 200, ['Content-Type' => 'application/javascript']);

    try {
      $httpResponse = $this->httpClient->get($this->atmApiHelper->get('sw_js_file'));
      $response->setContent($httpResponse->getBody()->getContents());
    }
    catch (ClientException $e) {

    }

    return $response;
  }

  /**
   * Return terms content.
   */
  public function getTerms() {
    $ajaxResponse = new JsonResponse();

    $cache = $this->cache->get('atm-terms');
    if ($cache) {
      $ajaxResponse->setData([
        'errors' => FALSE,
        'content' => $cache->data,
      ]);
    }
    else {
      try {
        $response = $this->httpClient->get($this->atmApiHelper->get('terms_dialog_url'));
        $content = $response->getBody()->getContents();

        $this->cache->set('atm-terms', $content);

        $ajaxResponse->setData([
          'errors' => FALSE,
          'content' => $content,
        ]);
      }
      catch (ClientException $exception) {
        $ajaxResponse->setData([
          'errors' => TRUE,
          'content' => $exception->getMessage(),
        ]);
      }
    }

    return $ajaxResponse;
  }

}
