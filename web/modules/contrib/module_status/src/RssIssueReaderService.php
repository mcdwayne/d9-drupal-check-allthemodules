<?php

namespace Drupal\module_status;

use Drupal\Core\Extension\ModuleHandlerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Response;

/**
 * Class RssIssueReaderService.
 */
class RssIssueReaderService implements RssIssueReaderServiceInterface {

  private $promises = [];

  const STATUS_OK = 200;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private $moduleHandler;

  /**
   * @var \GuzzleHttp\Client
   */
  private $client;

  /**
   * @var null|int
   */
  private $countOfAllIssues = NULL;

  /**
   * @var null|array
   */
  private $issues = NULL;

  /**
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \GuzzleHttp\Client $client
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    Client $client
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->client = $client;
  }

  /**
   * @return array
   */
  public function getModuleIssues() {
    if ($this->issues === NULL) {
      $this->retrieveIssues();
    }
    return $this->issues;
  }

  /**
   * @return int
   */
  public function getCountOfAllIssues() {
    if ($this->countOfAllIssues === NULL) {
      $this->retrieveIssues();
    }
    return $this->countOfAllIssues;
  }

  private function retrieveIssues() {
    $this->issues = [];
    $this->countOfAllIssues = 0;
    $moduleNames = $this->getAllNonCoreModuleNames();
    $results = $this->getRssCallResults($moduleNames);
    foreach ($results as $moduleName => $result) {
      if ($result['state'] !== 'fulfilled'
        || !isset($result['value'])
        || $result['value']->getStatusCode() !== self::STATUS_OK) {
        $this->issues[$moduleName]['callSuccessful'] = FALSE;
        continue;
      }
      /** @var \GuzzleHttp\Psr7\Response */
      $moduleRequestResults = $result['value'];

      $criticalIssues = $this->getCriticalIssuesFromResults($moduleRequestResults);
      $countIssues = count($criticalIssues);
      $this->issues[$moduleName]['callSuccessful'] = TRUE;
      $this->issues[$moduleName]['count'] = $countIssues;
      $this->issues[$moduleName]['link'] = $this->getLinkToIssuesByModuleName($moduleName);
      $this->countOfAllIssues += $countIssues;
    }
  }

  /**
   * @return array
   */
  private
  function getAllNonCoreModuleNames() {
    $moduleList = $this->moduleHandler->getModuleList();
    $moduleNames = [];
    foreach ($moduleList as $moduleNameKey => $module) {
      if (substr($module->getPathname(), 0, 4) === "core") {
        continue;
      }
      $moduleNames[] = $moduleNameKey;
    }
    return $moduleNames;
  }

  /**
   * @param $moduleNames
   *
   * @return array
   */
  private
  function getRssCallResults($moduleNames) {
    foreach ($moduleNames as $moduleName) {
      $rssUrl = $this->getRssUrlFromModuleName($moduleName);
      $this->promises[$moduleName] = $this->client->getAsync($rssUrl);
    }
    $results = Promise\settle($this->promises)->wait();
    return $results;
  }

  /**
   * @param string $moduleName
   *
   * @return string
   */
  private
  function getRssUrlFromModuleName($moduleName) {
    return 'https://www.drupal.org/project/issues/rss/' . $moduleName . '?priorities=400&categories=1&version=8.x';
  }

  /**
   * @param \GuzzleHttp\Psr7\Response $moduleRequestResults
   *
   * @return mixed
   */
  private
  function getCriticalIssuesFromResults(Response $moduleRequestResults) {
    $body = (string) $moduleRequestResults->getBody();
    $simpleXMLElement = new \SimpleXMLElement(trim($body));
    $criticalIssues = $simpleXMLElement->channel->item;
    return $criticalIssues;
  }

  /**
   * @param $moduleName
   *
   * @return string
   */
  private
  function getLinkToIssuesByModuleName($moduleName) {
    return 'https://www.drupal.org/project/issues/' . $moduleName . '?priorities=400&categories=1&version=8.x';
  }

}
