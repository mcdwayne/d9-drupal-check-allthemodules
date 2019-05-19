<?php

/**
 * @file
 */

namespace Drupal\smsc\Smsc;


use GuzzleHttp\Exception\RequestException;
use Smsc\Request\RequestInterface;
use Smsc\Services\AbstractSmscService;


class DrupalSmscRequest implements RequestInterface {

  /**
   * Execute request.
   *
   * @param AbstractSmscService $service
   *
   * @return string
   * @throws \Exception
   */
  public function execute(AbstractSmscService $service) {

    $settings = $service->getSettings();

    $uri  = $service->getApiUrl();
    $body = $settings->getPostData();

    try {
      $response = \Drupal::httpClient()->post($uri, [
        'headers' => [
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'body'    => $body,
      ]);

      return (string) $response->getBody();
    }
    catch (RequestException $e) {
      watchdog_exception('smsc', $e);
    }
  }
}
