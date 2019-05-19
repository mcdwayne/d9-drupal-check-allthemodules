<?php

namespace Drupal\wunderstatus;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class WunderstatusService {

  const CONNECT_TIMEOUT_SECONDS = 5;
  const REQUEST_TIMEOUT_SECONDS = 5;

  /** @var Client */
  protected $client;

  /** @var LoggerChannel */
  protected $logger;
  
  /** @var StateInterface */
  protected $state;

  /** @var WunderstatusInfoCollector */
  protected $wunderstatusInfoCollector;

  public function __construct(Client $client, LoggerChannel $logger, StateInterface $state, WunderstatusInfoCollector $wunderstatusInfoCollector) {
    $this->client = $client;
    $this->logger = $logger;
    $this->state = $state;
    $this->wunderstatusInfoCollector = $wunderstatusInfoCollector;
  }

  /**
   * Sends information about enabled modules to manager site (excluding core
   * modules).
   * 
   * @return bool|mixed|\Psr\Http\Message\ResponseInterface
   */
  public function sendModuleInfo() {
    $response = FALSE;

    if (empty($this->getKey())) {
      $this->logger->warning('Wunderstatus authentication key is not set.');
    }
    elseif (empty($this->getManagerEndpointUrl())) {
      $this->logger->warning('Wunderstatus manager endpoint URL is not set.');
    }
    else {
      try {
        $response = $this->client->request('POST', $this->getManagerEndpointUrl(), $this->buildRequestOptions());
        $this->logger->notice('Status information sent.');
      }
      catch (RequestException $e) {
        $this->handleNonOkResponse($e);
      }
    }

    return $response;
  }

  private function getKey() {
    return $this->state->get('wunderstatus_key');
  }

  private function getManagerEndpointUrl() {
    return $this->state->get('wunderstatus_manager_endpoint_url');
  }

  private function buildRequestOptions() {
    $options = [
      RequestOptions::BODY => $this->buildRequestBody(),
      RequestOptions::CONNECT_TIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
      RequestOptions::TIMEOUT => self::REQUEST_TIMEOUT_SECONDS
    ];

    if (!empty($this->getAuthUsername()) && !empty($this->getAuthPassword())) {
      $options[RequestOptions::AUTH] = [$this->getAuthUsername(), $this->getAuthPassword()];
    }

    return $options;
  }

  private function getAuthUsername() {
    return $this->state->get('wunderstatus_auth_username');
  }

  private function getAuthPassword() {
    return $this->state->get('wunderstatus_auth_password');
  }

  private function buildRequestBody() {
    return Json::encode([
      'key' => $this->getKey(),
      'modules' => $this->wunderstatusInfoCollector->getVersionInfo(),
      'siteName' => \Drupal::config('system.site')->get('name'),
      'siteUuid' => \Drupal::config('system.site')->get('uuid'),
    ]);
  }

  private function handleNonOkResponse(RequestException $e) {
    $response = $e->getResponse();

    if ($response) {
      $this->logger->warning(
        'Status information send failed. Response: @response',
        ['@response' => (string) $response->getBody()]
      );
    } else {
      watchdog_exception('wunderstatus', $e);
    }
  }
}