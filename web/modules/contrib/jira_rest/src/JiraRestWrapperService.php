<?php

namespace Drupal\jira_rest;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\Entity\Key;
use biologis\JIRA_PHP_API\GuzzleCommunicationService;
use biologis\JIRA_PHP_API\IssueService;
use GuzzleHttp\Client;

/**
 * Class JiraRestWrapperService.
 *
 * @package Drupal\jira_rest
 */
class JiraRestWrapperService {

  /**
   * Issue API service.
   *
   * @var \biologis\JIRA_PHP_API\IssueService
   */
  protected $issueService;

  /**
   * JiraRestWrapperService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {

    // Current credentials and url coming from drupal config form.
    $config = $config_factory->get('jira_rest.settings');
    $password_key = Key::load($config->get('jira_rest.password'));

    $credents = [
      'username' => $config->get('jira_rest.username'),
      'password' => ($password_key) ? $password_key->getKeyValue() : '',
    ];

    $communicationService = new GuzzleCommunicationService($config->get('jira_rest.instanceurl') . '/rest/api/2/', $credents);
    $this->issueService = new IssueService($communicationService);

  }

  /**
   * Get the Issue service api.
   *
   * @return \biologis\JIRA_PHP_API\IssueService
   *   Issue Service API.
   */
  public function getIssueService() {
    return $this->issueService;
  }

  /**
   * EXPERIMENTAL, will probably change, this later will be included as a function of \biologis\JIRA_PHP_API\Issue
   *
   * @param string $path
   * @param string $data
   * @return bool|mixed
   */
  public function attachFileToIssueByKey($file_path, $issuekey) {

    $path = 'issue/' . $issuekey  . '/attachments';

    $multipart =

      [
        [
          'name'     => 'file',
          'contents' => fopen($file_path, 'r'),
        ],
      ];

    $options = array(
      'multipart' => $multipart,
      'headers' => array(
        'X-Atlassian-Token' => 'nocheck',
      )
    );

    //current credentials and url coming from drupal config form
    $config = \Drupal::config('jira_rest.settings');

    $options += array(
      'auth' => array(
        $config->get('jira_rest.username'),
        $config->get('jira_rest.password')
      )
    );

    try {

      $guzzleHTTPClient = new Client([
        'base_uri' => $config->get('jira_rest.instanceurl') . '/rest/api/2/',
        'timeout'  => 10.0,
      ]);
      $response = $guzzleHTTPClient->request('POST', $path, $options);

      if ($response->getStatusCode() == 201 || $response->getStatusCode() == 200) {
        $response_content = json_decode($response->getBody()->getContents());
        return $response_content;
      }
      else {
        return false;
      }
    } catch (\Exception $e) {
      return false;
    }
  }
}
