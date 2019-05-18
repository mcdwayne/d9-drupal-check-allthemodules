<?php

namespace Drupal\bitbucket_issues\Plugin\GitIssues;

use Drupal\Core\Url;
use Drupal\git_issues\Plugin\GitIssues\GitIssuesBase;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Bitbucket Plugin.
 *
 * @GitIssuesPlugin(
 *   id = "bitbucket",
 *   gitLabel = "Bitbucket"
 * )
 */
class Bitbucket extends GitIssuesBase {
  /**
   * The GitLab API base url address.
   *
   * @var string
   */
  private $apiBaseUrl;

  /**
   * The GitLab API key.
   *
   * @var string
   */
  private $apiKey;

  /**
   * The GitLab API secret.
   *
   * @var string
   */
  private $apiSecret;

  /**
   * The GitLab API access token.
   *
   * @var string
   */
  private $accessToken;

  /**
   * The GitLab API refresh token.
   *
   * @var string
   */
  private $refreshToken;

  /**
   * The GitLab chosen project.
   *
   * @var string
   */
  private $projectId;

  /**
   * Variable that provides \GuzzleHttp\Client object.
   *
   * @var \GuzzleHttp\Client
   */
  private $client;

  /**
   * Config instance.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $gitSettings;

  /**
   * Logged in username.
   *
   * @var string
   */
  private $username;

  /**
   * Temporary storage.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  private $tempStorage;

  /**
   * Constructs a new Bitbucket plugin object.
   */
  public function __construct() {
    $this->client = \Drupal::httpClient();
    $this->gitSettings = \Drupal::config('git_issues.settings');

    $this->tempStorage = \Drupal::service('user.private_tempstore')->get('bitbucket_issues');

    $this->accessToken = $this->tempStorage->get('access_token');
    $this->refreshToken = $this->tempStorage->get('refresh_token');

    $this->apiBaseUrl = $this->gitSettings->get('plugins.settings')['api_base_url'];
    $this->apiKey = $this->gitSettings->get('plugins.settings')['api_key'];
    $this->apiSecret = $this->gitSettings->get('plugins.settings')['api_secret'];
    $this->projectId = $this->gitSettings->get('plugins.settings')['project_id'];

    $this->auth();

    if ((isset($this->apiBaseUrl) && (!empty($this->apiBaseUrl) && !is_null($this->apiBaseUrl))) &&
      (isset($this->accessToken) && (!empty($this->accessToken) && !is_null($this->accessToken)))
    ) {
      $this->username = $this->getUser()['username'];
    }

    $this->menuStateToggle('git_issues.issues.closed', FALSE);
  }

  /**
   * Getting care about all API calls in module.
   *
   * {@inheritdoc}
   *
   * @param string $action
   *   Action of http call ('get', 'post', 'put', 'patch'...).
   * @param string $url
   *   API url.
   * @param bool $auth
   *   Authenticate user if is true.
   * @param bool $refresh
   *   Refresh authentication if is true.
   * @param string $jsonContent
   *   JSON content.
   *
   * @return array
   *   Returns API call result.
   */
  private function doCall($action, $url, $auth = FALSE, $refresh = FALSE, $jsonContent = NULL) {
    if (!$auth) {
      $headers = [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->accessToken,
        ],
      ];

      if (!is_null($jsonContent)) {
        $headers['headers']['Content-Type'] = 'application/json';
        $headers['body'] = $jsonContent;
      }

    }
    else {

      $formParams = [];
      $formParams['grant_type'] = 'client_credentials';

      if (!is_null($refresh)) {
        $formParams['grant_type'] = 'refresh_token';
        $formParams['refresh_token'] = $this->refreshToken;
      }

      $headers = [
        'form_params' => $formParams,
        'headers' => [
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
      ];
    }

    try {
      $response = $this->client->{$action}($url, $headers);
      $data = json_decode($response->getBody()->getContents(), TRUE);

      return $data;
    }
    catch (RequestException $e) {
      \Drupal::logger('git_issues')->notice('API Error message: %msg.', ['%msg' => $e->getMessage()]);

      if ($e->getCode() == 401) {
        if (is_null($this->refreshToken) || empty($this->refreshToken)) {
          $expired = FALSE;
        }
        else {
          $expired = TRUE;
        }

        $this->auth($expired);
        $this->doCall($action, $url);
      }
    }
  }

  /**
   * Authenticate user function.
   *
   * {@inheritdoc}
   */
  private function auth($expired = FALSE) {
    if ((is_null($this->apiKey) || empty($this->apiKey)) || (is_null($this->apiSecret) || empty($this->apiSecret))) {
      return FALSE;
    }

    if ((is_null($this->accessToken) || empty($this->accessToken)) || $expired) {
      $url = "https://$this->apiKey:$this->apiSecret@bitbucket.org/site/oauth2/access_token";
      $data = $this->doCall('post', $url, TRUE, $expired);

      if (!is_null($data['access_token']) && !empty($data['access_token'])) {
        $this->tempStorage->set('access_token', $data['access_token']);
        $this->tempStorage->set('refresh_token', $data['refresh_token']);
      }
    }
  }

  /**
   * Get project issues list.
   *
   * {@inheritdoc}
   *
   * @return array
   *   Returns Issues list.
   */
  public function getProjectIssues() {
    if (!isset($this->projectId) || (empty($this->projectId) || is_null($this->projectId))) {
      return FALSE;
    }
    else {
      $list = [];

      // Define table headers.
      $list['table'] = [
        '#theme' => 'table',
        '#header' => [
          'title' => [
            'data' => t('Issue'),
          ],
          'state' => [
            'data' => t('Status'),
          ],
          'web_url' => [
            'data' => t('URL'),
          ],
          'last_update' => [
            'data' => t('Last update'),
          ],
          'priority' => [
            'data' => t('Priority'),
          ],
          'category' => [
            'data' => t('Category'),
          ],
        ],
        '#rows' => [],
      ];

      $url = $this->apiBaseUrl . "repositories/$this->username/$this->projectId/issues";
      $issues = $this->doCall('get', $url);

      foreach ($issues['values'] as $issue) {
        $url = Url::fromRoute('git_issues.issue.view', ['issueId' => $issue['id']]);
        $internalLink = \Drupal::l($issue['title'], $url);

        $list['table']['#rows'][] = [
          'data' => [
            'title' => $internalLink,
            'state' => $issue['state'],
            'web_url' => $issue['links']['html']['href'],
            'last_update' => $this->getUpdatedByString($issue),
            'priority' => $issue['priority'],
            'category' => $issue['kind'],
          ],
        ];
      }
      $list['table']['#empty'] = 'There are no issues.';

      return $list;
    }
  }

  /**
   * Get single issue.
   *
   * {@inheritdoc}
   *
   * @param int $issueId
   *   Issue id.
   *
   * @return array
   *   Returns view od single issue.
   */
  public function getIssue($issueId) {
    $url = $this->apiBaseUrl . "repositories/$this->username/$this->projectId/issues/$issueId";
    $issueApi = $this->doCall('get', $url);

    $issue = [
      'title' => $issueApi['title'],
      'author' => [
        'name' => $issueApi['assignee']['username'],
      ],
      'created_at' => (new \DateTime($issueApi['created_on']))->format('m/d/Y H:i'),
      'labels' => [$issueApi['kind']],
      'state' => $issueApi['state'],
      'priority' => $issueApi['priority'],
      'description' => $issueApi['content']['raw'],
      'id' => $issueApi['id'],
      'assignee' => [
        'id' => $issueApi['assignee']['username'],
      ],
      'updated_string' => $this->getUpdatedByString($issueApi, TRUE),
    ];

    $comments = [];
    foreach ($this->getIssueComments($issueApi['id'])['values'] as $comment) {
      if (!empty($comment['content']['raw'])) {
        $comments[$comment['id']] = [
          'body' => $comment['content']['raw'],
          'author' => [
            'name' => $comment['user']['username'],
          ],
          'created_at' => $comment['created_on'],
        ];
      }
    }

    return [
      '#title' => $issue['title'],
      '#theme' => 'issue_view',
      '#issue' => $issue,
      '#comments' => $comments,
      '#actions' => [
        'new' => 'new',
        'open' => 'open',
        'resolved' => 'resolved',
        'on hold' => 'on hold',
        'invalid' => 'invalid',
        'duplicate' => 'duplicate',
        'wontfix' => 'wontfix',
        'closed' => 'closed',
      ],
      '#edit' => FALSE,
    ];
  }

  /**
   * Retrieves the settings form of plugin.
   *
   * {@inheritdoc}
   *
   * @return array
   *   Returns form array.
   */
  public function getSettingsForm() {

    parse_str(\Drupal::request()->getQueryString(), $params);

    if (array_key_exists('error', $params)) {
      if ($params['error']) {
        drupal_set_message(t('It is necessary to fill the following fields'), 'error');
      }
    }

    $form = [
      'api_base_url' => [
        '#type' => 'textfield',
        '#title' => t('Bitbucket Base url'),
        '#description' => t('Bitbucket Base url'),
        '#default_value' => $this->apiBaseUrl,
      ],
      'api_key' => [
        '#type' => 'textfield',
        '#title' => t('Bitbucket API key'),
        '#description' => t('Bitbucket API key'),
        '#default_value' => $this->apiKey,
      ],
      'api_secret' => [
        '#type' => 'textfield',
        '#title' => t('Bitbucket API secret'),
        '#description' => t('Bitbucket API secret'),
        '#default_value' => $this->apiSecret,
      ],
    ];

    $options = [];
    if (!is_null($this->accessToken) && !empty($this->accessToken)) {
      $projects = $this->getProjects();

      foreach ($projects['values'] as $project) {
        $options[$project['slug']] = $project['name'];
      }
    }

    if (count($options) > 0) {
      $form['projects'] = [
        '#type' => 'radios',
        '#title' => t('Projects'),
        '#options' => $options,
        '#attributes' => ['name' => 'projects'],
      ];
    }
    if (!is_null($this->projectId)) {
      $form['projects']['#default_value'] = $this->projectId;
    }

    return $form;

  }

  /**
   * Submit the settings form of plugin.
   *
   * {@inheritdoc}
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface object.
   */
  public function submitSettingsForm(array $form, FormStateInterface $form_state) {
    ($form_state->getValue('plugins') != $form['plugins']['#default_value']) ? $resetParams = TRUE : $resetParams = FALSE;

    \Drupal::configFactory()->getEditable('git_issues.settings')
      ->set('plugins.settings', $this->getSettingsFormParams($form_state, $resetParams))
      ->save();
  }

  /**
   * Retrieves the add/edit form of single issue.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface object.
   * @param array $vars
   *   Form variables.
   *
   * @return array
   *   Returns form array.
   */
  public function getIssueForm(FormStateInterface $form_state, array $vars) {
    $form = [
      'issue_title' => [
        '#type' => 'textfield',
        '#title' => t('Title'),
        '#weight' => 1,
      ],
      'issue_description' => [
        '#type' => 'textarea',
        '#title' => t('Description'),
        '#weight' => 5,
      ],
      'issue_priority' => [
        '#type' => 'select',
        '#title' => t('Priority'),
        '#options' => [
          'trivial' => 'trivial',
          'minor' => 'minor',
          'major' => 'major',
          'critical' => 'critical',
          'blocker' => 'blocker',
        ],
        '#weight' => 5,
      ],
      'labels' => [
        '#type' => 'details',
        '#title' => t('Labels'),
        '#open' => TRUE,
        '#weight' => 6,
      ],
      'save' => [
        '#type' => 'submit',
        '#value' => t('Save'),
        '#name' => 'main_issue_form_submit',
        '#weight' => 7,
      ],
    ];

    $form['labels']['issue_label_search'] = [
      '#title' => t('Search and select a category'),
      '#type' => 'textfield',
      '#attributes' => [
        'class' => ['issue-label-search'],
      ],
    ];

    $form['labels']['issue_labels'] = [
      '#type' => 'radios',
      '#options' => [
        'bug' => 'bug',
        'enhancement' => 'enhancement',
        'proposal' => 'proposal',
        'task' => 'task',
      ],
      '#attributes' => [
        'class' => ['issue-label-item'],
      ],
    ];

    return $form;
  }

  /**
   * Retrieves the single issue add/edit form.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface object.
   */
  public function submitIssueForm(FormStateInterface $form_state) {
    $vars = [
      'title' => $form_state->getValue('issue_title'),
      'content' => [
        'raw' => $form_state->getValue('issue_description'),
      ],
      'kind' => $form_state->getValue('issue_labels'),
      'priority' => $form_state->getValue('issue_priority'),
    ];

    $this->addIssue($vars);

    $url = Url::fromRoute('git_issues.issues');
    $form_state->setRedirectUrl($url);
  }

  /**
   * Retrieves projects from GitLab API.
   *
   * {@inheritdoc}
   *
   * @return array
   *   Returns array with projects list.
   */
  public function getProjects() {
    return $this->doCall('get', $this->apiBaseUrl . 'repositories/' . $this->username);
  }

  /**
   * Retrieves logged in user from GitLab API.
   *
   * {@inheritdoc}
   *
   * @return array
   *   Returns array with user data.
   */
  public function getUser() {
    $url = $this->apiBaseUrl . "user";

    return $this->doCall('get', $url);
  }

  /**
   * Create single issue, makes post call to GitLab.
   *
   * {@inheritdoc}
   *
   * @oaram $vars
   *   Array with issue params.
   */
  public function addIssue(array $vars) {
    return $this->doCall('post', $this->apiBaseUrl . "repositories/$this->username/$this->projectId/issues", FALSE, FALSE, json_encode($vars));
  }

  /**
   * Helper function that retrieves settings form parameters.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   It needs the FormStateInterface object.
   * @param bool $resetParams
   *   It needs bool value that reset settings form parameters.
   */
  public function getSettingsFormParams(FormStateInterface $form_state, $resetParams) {
    if (!$resetParams) {
      return [
        'api_key' => $form_state->getValue('api_key'),
        'api_secret' => $form_state->getValue('api_secret'),
        'api_base_url' => $form_state->getValue('api_base_url'),
        'project_id' => $form_state->getValue('projects'),
      ];
    }
    else {
      return [];
    }
  }

  /**
   * Helper function that retrieves "updated by" string.
   *
   * {@inheritdoc}
   *
   * @param array $issue
   *   It needs array with issue.
   * @param bool $parts
   *   It needs bool value that indicates to return parts.
   */
  public function getUpdatedByString(array $issue, $parts = FALSE) {
    $comments = array_reverse($this->getIssueComments($issue['id'])['values']);
    $date = (new \DateTime($issue['updated_on']))->format('m/d/Y H:i');

    if (count($comments) > 0) {
      $author = $comments[0]['user']['username'];
    }
    else {
      $author = $issue['reporter']['username'];
    }

    if (!$parts) {
      return $date . ', updated by ' . $author;
    }
    else {
      return [
        'date' => $date,
        'author' => $author,
      ];
    }
  }

  /**
   * Get single issue comments.
   *
   * {@inheritdoc}
   *
   * @param int $issueId
   *   GitLab issue id.
   *
   * @return array
   *   Returns array with issue comments.
   */
  public function getIssueComments($issueId) {
    return $this->doCall('get', $this->apiBaseUrl . "repositories/$this->username/$this->projectId/issues/$issueId/comments");
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Config\ConfigFactory $config */
    $config = $container->get('config.factory');
    return new static($config);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'git_issues.settings',
    ];
  }

}
