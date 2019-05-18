<?php

namespace Drupal\gitlab_time_tracker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Gitlab\Client;
use Gitlab\ResultPager;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class GitlabClient.
 */
class GitlabClient implements GitlabClientInterface {

  /**
   * Drupal\Core\Config\ConfigManagerInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * \GuzzleHttp\Client instance.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;
  /**
   * Constructs a new GitlabClient object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, HttpClient $http_client, Session $session) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->session = $session;
  }

  protected function getClient() {
    $config = Settings::get('gitlab');

    if ($user_token = $this->session->get('gitlab_token')) {
      $token = $user_token;
    }
    else {
      $token = $config['api_token'];
    }

    return Client::create($config['url'])
      ->authenticate($config['api_token'], Client::AUTH_URL_TOKEN);
  }

  protected function getRequest($endpoint, $page = 1, $total_pages = NULL) {
    $config = Settings::get('gitlab');

    if ($user_token = $this->session->get('gitlab_token')) {
      $token = $user_token->getToken();
      $headers = [
        'Authorization' => "Bearer {$token}",
      ];
    }
    else {
      $token = $config['api_token'];

      $headers = [
        'Private-Token' => "{$token}",
      ];
    }

    try {
      $response = $this->httpClient->request(
        'GET',
        $config['url'] . $endpoint,
        [
          'query' => [
            'per_page' => '20',
            'page' => $page,
            'private_token' => $token,
            'synchronous' => TRUE,
          ],
          'headers' => $headers,
        ]
      );
      $headers = $response->getHeaders();

      if ($response->getStatusCode() == '200') {
        $results = json_decode($response->getBody()->getContents(), TRUE);
      }

      if (isset($headers['X-Total-Pages'])) {
        // Use recursion to fetch all related content.
        $total_pages = is_numeric($total_pages) ? $total_pages : reset($headers['X-Total-Pages']);
      }
      else {
        $total_pages = 1;
      }

      if ($page < $total_pages) {
        return array_merge($results, $this->getRequest($endpoint, $page + 1, $total_pages));
      }
      else {
        return $results;
      }
    }
    catch (\Exception $e) {
      return [];
    }
  }

  protected function getPager() {
    $pager = new ResultPager($this->getClient());
    return $pager;
  }

  public function fetchUsers() {
    return $this->getRequest('users');
  }

  public function fetchProjects($project_id = NULL) {
    if (!is_null($project_id)) {
      return $this->getRequest("projects/{$project_id}");
    }
    else {
      return $this->getRequest('projects');
    }
  }

  public function fetchIssues($project_id = NULL) {
    if (is_null($project_id)) {
      return $this->getRequest('issues');
    }
    else {
      return $this->getRequest("projects/{$project_id}/issues");
    }
  }

  public function fetchComments($project_id, $issue_id) {
    return $this->getRequest("projects/{$project_id}/issues/{$issue_id}/notes");
  }
}
