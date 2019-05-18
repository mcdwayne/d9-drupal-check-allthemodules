<?php

namespace Drupal\clever_reach\Component\Infrastructure;

use CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Interfaces\Exposed\Runnable;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException;
use CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\Infrastructure\Utility\GuidProvider;
use Drupal\clever_reach\Component\Repository\ProcessRepository;
use Drupal\Core\Url;

/**
 * Implementation of async process starter interface.
 *
 * @see \CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter
 */
class AsyncProcessStarterService implements AsyncProcessStarter {
  const XDEBUG_KEY = '';

  /**
   * Http client service instance.
   *
   * @var \CleverReach\Infrastructure\Interfaces\Required\HttpClient
   */
  private $httpClientService;

  /**
   * {@inheritdoc}
   */
  public function start(Runnable $runner) {
    $guidProvider = new GuidProvider();
    $guid = trim($guidProvider->generateGuid());

    $this->saveGuidAndRunner($guid, $runner);
    $this->startRunnerAsynchronously($guid);
  }

  /**
   * Saves runner and guid to storage.
   *
   * @param string $guid
   *   Unique request ID.
   * @param \CleverReach\Infrastructure\Interfaces\Exposed\Runnable $runner
   *   Runner object.
   *
   * @throws ProcessStarterSaveException
   */
  private function saveGuidAndRunner($guid, Runnable $runner) {
    try {
      $processRepository = new ProcessRepository();
      $processRepository->save($guid, $runner);
    }
    catch (\Exception $e) {
      Logger::logError($e->getMessage(), 'Integration');
      throw new ProcessStarterSaveException($e->getMessage(), 0, $e);
    }
  }

  /**
   * Starts runnable asynchronously.
   *
   * @param string $guid
   *   Unique request ID.
   *
   * @throws HttpRequestException
   */
  private function startRunnerAsynchronously($guid) {
    try {
      $this->getHttpClient()->requestAsync('POST', $this->formatUrl($guid));
    }
    catch (\Exception $e) {
      Logger::logError($e->getMessage(), 'Integration');
      throw new HttpRequestException($e->getMessage(), 0, $e);
    }
  }

  /**
   * Gets process URL with all included parameters.
   *
   * @param int $guid
   *   Unique request ID.
   *
   * @return string
   *   Url to process controller.
   */
  private function formatUrl($guid) {
    $params = ['action' => 'run', 'guid' => $guid];

    if (self::XDEBUG_KEY) {
      $params['XDEBUG_SESSION_START'] = self::XDEBUG_KEY;
    }

    return Url::fromRoute('cleverreach.cleverreach.process', $params, ['absolute' => TRUE])->toString();
  }

  /**
   * Gets HTTP client.
   *
   * @return \CleverReach\Infrastructure\Interfaces\Required\HttpClient
   *   Instance of http client.
   */
  private function getHttpClient() {
    if (NULL === $this->httpClientService) {
      $this->httpClientService = ServiceRegister::getService(HttpClient::CLASS_NAME);
    }

    return $this->httpClientService;
  }

}
