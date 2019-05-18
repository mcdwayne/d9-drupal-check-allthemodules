<?php

namespace Drupal\gutenberg\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\RequestException;
use League\Container\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class OEmbedProxyUrlController.
 *
 * @package Drupal\gutenberg\Controller
 */
class OEmbedProxyUrlController extends ControllerBase {

  /**
   * HTTP request.
   *
   * @return bool|\Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function request() {
    if (empty($_GET['url']) || !UrlHelper::isValid($_GET['url'], TRUE)) {
      throw new BadRequestHttpException("No valid URL was provided.");
    }

    try {
      $response = \Drupal::httpClient()->request('GET', $_GET['url']);

      $data    = $response->getBody()->getContents();
      $status  = $response->getStatusCode();
      $headers = $response->getHeaders();
      $json    = !empty($headers['Content-Type']) ? $headers['Content-Type'][0] == 'application/json' : FALSE;

      return new JsonResponse(json_decode($data));
    }
    catch (RequestException $e) {
      watchdog_exception('Gutenberg Editor', $e->getMessage());

      throw new NotFoundException("The provided URL was not found.");
    }
  }

}
