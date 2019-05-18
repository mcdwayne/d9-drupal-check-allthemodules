<?php

namespace Drupal\github_issues\Plugin\GitIssues;

use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;
use Drupal\git_issues\Plugin\GitIssues\GitIssuesBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a GitHub Plugin.
 *
 * @GitIssuesPlugin(
 *   id = "github",
 *   gitLabel = "GitHub"
 * )
 */
class GitHub extends GitIssuesBase {

  /**
   * The GitHub API base url address.
   *
   * @var string
   */
  private $apiBaseUrl;

  /**
   * The GitHub API private token.
   *
   * @var string
   */
  private $privateToken;

  /**
   * The GitHub chosen project.
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
   * Constructs a new GitHub plugin object.
   */
  public function __construct() {
    $this->client = \Drupal::httpClient();
    $this->gitSettings = \Drupal::config('git_issues.settings');

    $this->apiBaseUrl = $this->gitSettings->get('plugins.settings')['base_url'];
    $this->privateToken = $this->gitSettings->get('plugins.settings')['access_token'];
    $this->projectId = $this->gitSettings->get('plugins.settings')['project_id'];

    if ((isset($this->apiBaseUrl) && (!empty($this->apiBaseUrl) && !is_null($this->apiBaseUrl))) &&
      (isset($this->privateToken) && (!empty($this->privateToken) && !is_null($this->privateToken)))
    ) {
      $this->username = $this->getUser()['login'];
    } else{
      drupal_set_message(t('It is necessary to fill the configuration form'), 'warning');
    }

    $this->menuStateToggle('git_issues.issues.closed', TRUE);
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
   * @param string $body
   *   JSON content.
   *
   * @return array
   *   Returns API call result.
   */
  private function doCall($action, $url, $body = NULL) {
    try {
      $response = $this->client->{$action}($url, [
        'headers' => [
          'Authorization' => 'token ' . $this->privateToken,
        ],
        'body' => $body,
      ]);
      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data;
    }
    catch (RequestException $e) {
      \Drupal::logger('git_issues')->notice('API Error message: %msg.', ['%msg' => $e->getMessage()]);
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
          'category' => [
            'data' => t('Category'),
          ],
        ],
        '#rows' => [],
      ];

      $vars = [];
      if (\Drupal::request()->query->all()['state'] == 'closed') {
        $vars = ['state' => 'closed'];
      }

      $url = $this->apiBaseUrl . "repos/$this->username/$this->projectId/issues?" . urldecode(http_build_query($vars));
      $issues = $this->doCall('get', $url);

      foreach ($issues as $issue) {

        $url = Url::fromRoute('git_issues.issue.view', ['issueId' => $issue['number']]);
        $internalLink = \Drupal::l($issue['title'], $url);

        $labels = $this->getIssueLabels($issue);

        $list['table']['#rows'][] = [
          'data' => [
            'title' => $internalLink,
            'state' => $issue['state'],
            'web_url' => $issue['url'],
            'last_update' => $this->getUpdatedByString($issue),
            'category' => implode(', ', $labels),

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
    $url = $this->apiBaseUrl . "repos/$this->username/$this->projectId/issues/$issueId";
    $issueApi = $this->doCall('get', $url);
    $commentsApi = $this->getIssueComments($issueApi['number']);

    $issue = [
      'title' => $issueApi['title'],
      'author' => [
        'name' => $issueApi['user']['login'],
      ],
      'created_at' => $issueApi['created_at'],
      'state' => $issueApi['state'],
      'labels' => $this->getIssueLabels($issueApi),
      'description' => $issueApi['body'],
      'id' => $issueApi['number'],
      'assignee' => [
        'id' => $issueApi['assignee']['id'],
      ],
      'updated_string' => $this->getUpdatedByString($issueApi, TRUE),
    ];

    $comments = [];
    foreach ($commentsApi as $comment) {
      $comments[$comment['id']] = [
        'body' => $comment['body'],
        'author' => [
          'name' => $comment['user']['login'],
        ],
        'created_at' => $comment['created_at'],
      ];
    }

    return [
      '#title' => $issue['title'],
      '#theme' => 'issue_view',
      '#issue' => $issue,
      '#comments' => $comments,
      '#actions' => [
        'closed' => 'open',
        'open' => 'close',
      ],
      '#edit' => TRUE,
    ];
  }

  /**
   * Change state of single issue.
   *
   * {@inheritdoc}
   *
   * @param int $issueId
   *   Issue id.
   * @param string $state
   *   Issue state.
   */
  public function issueChangeState($issueId, $state) {
    $url = $this->apiBaseUrl . "repos/$this->username/$this->projectId/issues/$issueId";

    return $this->doCall('patch', $url, json_encode(['state' => $state]));
  }

  /**
   * Post comment to an issue.
   *
   * {@inheritdoc}
   *
   * @param int $issueId
   *   Issue id.
   */
  public function postIssueComment($issueId) {
    $commentBody = \Drupal::request()->request->get('commentBody');
    $url = $this->apiBaseUrl . "repos/$this->username/$this->projectId/issues/$issueId/comments";

    return $this->doCall('post', $url, json_encode(['body' => $commentBody]));
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

    $form['git_hub'] = [
      '#type' => 'fieldset',
      '#title' => t('GitHub settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['git_hub']['base_url'] = [
      '#type' => 'textfield',
      '#title' => t('GitHub API Base URL'),
      '#description' => t('GitHub API Base URL (https://api.github.com/)'),
      '#default_value' => $this->apiBaseUrl,
    ];

    $form['git_hub']['access_token'] = [
      '#type' => 'textfield',
      '#title' => t('GitHub App Access Token'),
      '#description' => t('GitHub Access Token (https://help.github.com/articles/creating-a-personal-access-token-for-the-command-line)'),
      '#default_value' => $this->privateToken,
    ];

    $options = [];
    $projectsDefaultValue = NULL;

    if (!is_null($this->privateToken) && !empty($this->privateToken)) {
      $projects = $this->getProjects();

      foreach ($projects as $project) {
        $options[$project['name']] = $project['name'];
      }

      $projectsDefaultValue = $this->gitSettings->get('plugins.settings')['project_id'];
    }
    if (count($options) > 0) {
      $form['git_hub']['projects'] = [
        '#type' => 'radios',
        '#title' => 'Projects',
        '#options' => $options,
        '#attributes' => ['name' => 'projects'],
      ];

    }
    if (!is_null($projectsDefaultValue)) {
      $form['git_hub']['projects']['#default_value'] = $projectsDefaultValue;
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
    if ($vars['action'] == 'edit') {
      $vars['issue'] = $this->getIssue($vars['issueId']);
    }

    $labelDefaultValue = [];
    if ($vars['action'] == 'edit') {
      $issue = $vars['issue']['#issue'];
      $labelDefaultValue = array_keys($issue['labels']);
      $form_state->set('issue', $issue);

      $stateDefault = $issue['state'];
    }
    else {
      $stateDefault = 'open';
    }

    $labels = [];
    foreach ($this->getLabels() as $label) {
      $labels[$label['name']] = $label['name'];
    }

    $projectUsers = [];
    foreach ($this->getProjectUsers() as $user) {
      $projectUsers[$user['login']] = $user['login'];
    }

    $form_state->set('action', $vars['action']);

    $form = [
      'issue_title' => [
        '#type' => 'textfield',
        '#title' => t('Title'),
        '#default_value' => $issue['title'],
        '#weight' => 1,
      ],
      'issue_assign' => [
        '#type' => 'select',
        '#title' => t('Assign issue to'),
        '#options' => $projectUsers,
        '#weight' => 3,
        '#default_value' => $issue['assignee']['id'],
      ],
      'issue_description' => [
        '#type' => 'textarea',
        '#title' => t('Description'),
        '#default_value' => $issue['description'],
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
      '#type' => 'checkboxes',
      '#options' => $labels,
      '#default_value' => $labelDefaultValue,
      '#attributes' => [
        'class' => ['issue-label-item'],
      ],
    ];

    $form['labels']['issue_labels_submit'] = [
      '#type' => 'submit',
      '#value' => t('Create new label'),
      '#attributes' => [
        'class' => ['issue-label-submit', 'button--primary'],
      ],
      '#name' => 'save_label_issue_form_submit',
    ];

    if ($vars['action'] == 'edit') {
      $form['issue_state'] = [
        '#type' => 'select',
        '#title' => 'State',
        '#weight' => 2,
        '#options' => [
          'closed' => 'Close',
          'open' => 'Open',
        ],

        '#default_value' => $stateDefault,
      ];
    }

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
    $triggeringElement = $form_state->getTriggeringElement()['#name'];

    if ($triggeringElement == 'main_issue_form_submit') {
      $action = $form_state->get('action');

      if ($action == 'edit') {
        $state = $form_state->getValue('issue_state');
      }
      else {
        $state = 'open';
      }

      $actions = [
        'closed' => 'close',
        'open' => 'open',
      ];

      $labels = [];
      foreach ($form_state->getValue('issue_labels') as $label) {
        if ($label != '0') {
          $labels[] = $label;
        }
      }

      $vars = [
        'title' => $form_state->getValue('issue_title'),
        'body' => $form_state->getValue('issue_description'),
        'assignee' => $form_state->getValue('issue_assign'),
        'labels' => $labels,
      ];

      if ($action == 'edit') {
        $vars['state'] = $actions[$state];
        $issue = $form_state->get('issue');
        $this->editIssue($issue['id'], $vars);
        $url = Url::fromRoute('git_issues.issue.view', ['issueId' => $issue['id']]);
      }
      else {
        $this->addIssue($vars);
        $url = Url::fromRoute('git_issues.issues');
      }

      $form_state->setRedirectUrl($url);

    }
    elseif ($triggeringElement == 'save_label_issue_form_submit') {

      $vars = [
        'name' => $form_state->getValue('issue_label_search'),
        'color' => dechex(rand(0x000000, 0xFFFFFF)),
      ];

      $this->createLabel($vars);
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
    $comments = $issue['comments'];
    $date = (new \DateTime($issue['updated_at']))->format('m/d/Y H:i');

    if ($comments > 0) {
      $comment = array_reverse($this->getIssueComments($issue['number']));
      $author = $comment[0]['user']['login'];
    }
    else {
      $author = $issue['user']['login'];
    }

    if (!$parts) {
      if (count($comments) > 0) {
        return $date . ', updated by ' . $author;
      }
      else {
        return $date;
      }
    }
    else {
      return [
        'date' => $date,
        'author' => $author,
      ];
    }
  }

  /**
   * Create label, makes post call to GitLab.
   *
   * {@inheritdoc}
   *
   * @oaram array $vars
   *   Array with 'name' and 'color' members.
   */
  public function createLabel(array $vars) {
    $url = $this->apiBaseUrl . "repos/$this->username/$this->projectId/labels";

    return $this->doCall('post', $url, json_encode($vars));
  }

  /**
   * Retrieves projects users from GitLab API.
   *
   * {@inheritdoc}
   *
   * @return array
   *   Returns array with users related to project.
   */
  public function getProjectUsers() {
    $url = $this->apiBaseUrl . "repos/$this->username/$this->projectId/assignees";

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
    $url = $this->apiBaseUrl . "repos/$this->username/$this->projectId/issues";

    return $this->doCall('post', $url, json_encode($vars));
  }

  /**
   * Edit single issue, makes post call to GitLab.
   *
   * {@inheritdoc}
   *
   * @param int $issueId
   *   Issue id.
   * @param array $vars
   *   Array with issue params.
   */
  public function editIssue($issueId, array $vars) {
    $url = $this->apiBaseUrl . "repos/$this->username/$this->projectId/issues/$issueId";

    return $this->doCall('patch', $url, json_encode($vars));
  }

  /**
   * Retrieves issue labels from GitLab API.
   *
   * {@inheritdoc}
   *
   * @param array $issue
   *   Array with issue data.
   *
   * @return array
   *   Returns array with issue labels.
   */
  public function getIssueLabels(array $issue) {
    $labels = [];
    foreach ($issue['labels'] as $label) {
      $labels[$label['name']] = $label['name'];
    }

    return $labels;
  }

  /**
   * Retrieves labels from GitLab API.
   *
   * {@inheritdoc}
   *
   * @return array
   *   Returns array with labels.
   */
  public function getLabels() {
    $url = $this->apiBaseUrl . "repos/$this->username/$this->projectId/labels";

    return $this->doCall('get', $url);
  }

  /**
   * Get single issue comments.
   *
   * {@inheritdoc}
   *
   * @param int $number
   *   GitLab issue number.
   *
   * @return array
   *   Returns array with issue comments.
   */
  public function getIssueComments($number) {
    $url = $this->apiBaseUrl . "repos/$this->username/$this->projectId/issues/$number/comments";

    return $this->doCall('get', $url);
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
   * Retrieves projects from GitLab API.
   *
   * {@inheritdoc}
   *
   * @return array
   *   Returns array with projects list.
   */
  public function getProjects() {
    return $this->doCall('get', $this->apiBaseUrl . 'user/repos');
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
        'base_url' => $form_state->getValue('base_url'),
        'access_token' => $form_state->getValue('access_token'),
        'project_id' => $form_state->getValue('projects'),
      ];
    }
    else {
      return [];
    }
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
