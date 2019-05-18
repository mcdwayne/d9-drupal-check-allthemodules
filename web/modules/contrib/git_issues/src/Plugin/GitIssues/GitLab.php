<?php

namespace Drupal\git_issues\Plugin\GitIssues;

use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AjaxResponse;
use \Drupal\Core\Render\Element\Checkboxes;
use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a GitLab Plugin.
 *
 * @GitIssuesPlugin(
 *   id = "gitlab",
 *   gitLabel = "GitLab"
 * )
 */
class GitLab extends GitIssuesBase {

  /**
   * The GitLab API base url address.
   *
   * @var string
   */
  private $apiBaseUrl;

  /**
   * The GitLab API private token.
   *
   * @var string
   */
  private $privateToken;

  /**
   * The GitLab chosen project.
   *
   * @var string
   */
  private $projectId;

  /**
   * Variable that indicates all issues should be displayed.
   *
   * @var string
   */
  private $showAllIssues;

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
   * Constructs a new GitLab plugin object.
   */
  public function __construct() {
    $this->client = \Drupal::httpClient();
    $this->gitSettings = \Drupal::config('git_issues.settings');

    $this->apiBaseUrl = $this->gitSettings->get('plugins.settings')['base_url'];
    $this->privateToken = $this->gitSettings->get('plugins.settings')['access_token'];
    $this->showAllIssues = $this->gitSettings->get('plugins.settings')['show_all'];
    $this->projectId = $this->gitSettings->get('plugins.settings')['project_id'];

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
   *
   * @return array
   *   Returns API call result.
   */
  private function doCall($action, $url) {
    try {
      $response = $this->client->{$action}($url, [
        'headers' => [
          'PRIVATE-TOKEN' => $this->privateToken,
        ],
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

      // Prepare state column.
      if (\Drupal::request()->query->all()['state'] != 'closed') {
        $vars = [
          'state' => 'opened',
        ];
      }
      else {
        $vars = [
          'state' => 'closed',
        ];
      }

      if (!$this->showAllIssues) {
        $vars['author_id'] = $this->getUser()['id'];
      }

      $url = $this->apiBaseUrl . "projects/$this->projectId/issues?" . urldecode(http_build_query($vars));
      $issues = $this->doCall('get', $url);

      foreach ($issues as $issue) {
        $url = Url::fromRoute('git_issues.issue.view', ['issueId' => $issue['id']]);
        $internalLink = \Drupal::l($issue['title'], $url);

        $list['table']['#rows'][] = [
          'data' => [
            'title' => $internalLink,
            'state' => $issue['state'],
            'web_url' => $issue['web_url'],
            'last_update' => $this->getUpdatedByString($issue),
            'category' => implode(', ', $issue['labels']),
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
    $url = $this->apiBaseUrl . "projects/$this->projectId/issues/$issueId";
    $issue = $this->doCall('get', $url);
    $comments = $this->getIssueComments($issueId);

    $issue['updated_string'] = $this->getUpdatedByString($issue, TRUE);

    return [
      '#title' => $issue['title'],
      '#theme' => 'issue_view',
      '#issue' => $issue,
      '#comments' => array_reverse($comments),
      '#actions' => [
        'closed' => 'reopen',
        'opened' => 'close',
        'reopened' => 'close',
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
    return $this->doCall('put', $this->apiBaseUrl . "projects/$this->projectId/issues/$issueId?state_event=$state");
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
    return $this->doCall('post', $this->apiBaseUrl . "projects/$this->projectId/issues/$issueId/notes?body=$commentBody");
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

    $form['git_lab'] = [
      '#type' => 'fieldset',
      '#title' => t('GitLab settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $form['git_lab']['base_url'] = [
      '#type' => 'textfield',
      '#title' => t('GitLab Base url'),
      '#description' => t('GitLab Base url'),
      '#default_value' => $this->apiBaseUrl,
    ];

    $form['git_lab']['access_token'] = [
      '#type' => 'textfield',
      '#title' => t('GitLab App Access Token'),
      '#description' => t('GitLab Access Token'),
      '#default_value' => $this->privateToken,

    ];

    $options = [];
    $projectsDefaultValue = NULL;

    if (!is_null($this->privateToken) && !empty($this->privateToken)) {
      $projects = $this->getProjects();

      foreach ($projects as $project) {
        $options[$project['id']] = $project['name'];
      }

      $projectsDefaultValue = $this->gitSettings->get('plugins.settings')['project_id'];
    }
    if (count($options) > 0) {
      $form['git_lab']['projects'] = [
        '#type' => 'radios',
        '#prefix' => '<div id="gitlab-settings-form-wrapper">',
        '#suffix' => '</div>',
        '#title' => t('Projects'),
        '#options' => $options,
        '#attributes' => ['name' => 'projects'],
      ];

      $form['git_lab']['show_all'] = [
        '#type' => 'checkbox',
        '#title' => t('Show all issues'),
        '#default_value' => $this->showAllIssues,
      ];
    }

    if (!is_null($projectsDefaultValue)) {
      $form['git_lab']['projects']['#default_value'] = $projectsDefaultValue;
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
    // If is plugin changed reset form params.
    ($form_state->getValue('plugins') != $form['plugins']['#default_value']) ? $resetParams = TRUE : $resetParams = FALSE;
    $form_state->set('settings_vars', $this->getSettingsFormParams($form_state, $resetParams));
    $values = $form_state->get('settings_vars');

    \Drupal::configFactory()->getEditable('git_issues.settings')
      ->set('plugins.settings', $values)
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

    $vars['labels'] = $this->getLabels();
    $labels = [];

    foreach ($vars['labels'] as $label) {
      $labels[$label['name']] = $label['name'];
    }

    $labelDefaultValue = [];
    if ($vars['action'] == 'edit') {
      $issue = $vars['issue']['#issue'];
      $labelDefaultValue = $issue['labels'];
      $form_state->set('issue', $issue);

      $stateDefault = $issue['state'];
    }
    else {
      $stateDefault = 'opened';
    }

    $projectUsers = [];
    foreach ($this->getProjectUsers() as $user) {
      $projectUsers[$user['id']] = $user['name'];
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
        '#default_value' => $vars['issue']['#issue']['assignee']['id'],
      ],
      'issue_due_date' => [
        '#type' => 'date',
        '#title' => t('Due date'),
        '#default_value' => $vars['issue']['#issue']['due_date'],
        '#weight' => 4,
      ],
      'issue_description' => [
        '#type' => 'textarea',
        '#title' => t('Description'),
        '#default_value' => $issue['description'],
        '#weight' => 5,
      ],
      'labels' => [
        '#prefix' => '<div id="issue-labels">',
        '#suffix' => '</div>',
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
        'class' => [
          'issue-label-search',
        ],
      ],
    ];

    $form['labels']['issue_labels'] = [
      '#type' => 'checkboxes',
      '#options' => $labels,
      '#default_value' => $labelDefaultValue,
      '#attributes' => [
        'class' => [
          'issue-label-item',
        ],
      ],
    ];

    $form['labels']['issue_labels_submit'] = [
      '#type' => 'submit',
      '#value' => t('Create new label'),
      '#attributes' => [
        'class' => [
          'issue-label-submit',
          'button--primary',
        ],
      ],
      '#name' => 'save_label_issue_form_submit',
      '#ajax' => [
        'callback' => [$this, 'createLabelsCallback'],
        'wrapper'  => 'issue-labels',
      ],
    ];

    if ($vars['action'] == 'edit') {
      $form['issue_state'] = [
        '#type' => 'select',
        '#title' => t('State'),
        '#weight' => 2,
        '#options' => [
          'closed' => 'Close',
          'opened' => 'Open',
          'reopened' => 'Reopened',
        ],
        '#default_value' => $stateDefault,
      ];
    }

    return $form;
  }

  /**
   * Add new label callback function.
   */
  public function createLabelsCallback(array &$form, FormStateInterface $form_state) {
    $vars = [
      'name' => $form_state->getValue('issue_label_search'),
      'color' => '#' . sprintf('%06X', mt_rand(0, 16777215)),
    ];

    $this->createLabel($vars);

    $vars['labels'] = $this->getLabels();
    $labels = [];

    foreach ($vars['labels'] as $label) {
      $labels[$label['name']] = $label['name'];
    }

    $form['labels'] = [
      '#prefix' => '<div id="issue-labels">',
      '#suffix' => '</div>',
      '#type' => 'details',
      '#title' => t('Labels'),
      '#open' => TRUE,
      '#weight' => 6,
    ];

    $form['labels']['issue_label_search'] = [
      '#title' => t('Search and select a category'),
      '#type' => 'textfield',
      '#attributes' => [
        'class' => [
          'issue-label-search',
        ],
      ],
    ];

    $form['labels']['issue_labels'] = [
      '#prefix' => '<div id="issue-labels">',
      '#suffix' => '</div>',
      '#type' => 'checkboxes',
      '#options' => $labels,
      '#attributes' => [
        'class' => [
          'issue-label-item',
        ],
      ],
    ];

    $form['labels']['issue_labels_submit'] = [
      '#type' => 'submit',
      '#value' => t('Create new label'),
      '#attributes' => [
        'class' => [
          'issue-label-submit',
          'button--primary',
        ],
      ],
      '#name' => 'save_label_issue_form_submit',
      '#ajax' => [
        'callback' => [$this, 'createLabelsCallback'],
        'wrapper'  => 'issue-labels',
      ],
    ];

    Checkboxes::processCheckboxes($form['labels']['issue_labels'], $form_state, $form);

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#issue-labels', $form['labels']));

    return $response;

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

      if ($form_state->getValue('issue_state') != 'closed') {
        $state = substr($form_state->getValue('issue_state'), 0, -2);
      }
      else {
        $state = 'close';
      }

      $labels = [];
      foreach ($form_state->getValue('issue_labels') as $label) {
        if ($label != '0') {
          $labels[] = $label;
        }
      }

      if ($action == 'edit' && $state == 'open') {
        $state = 'reopen';
      }
      elseif ($action == 'add' && $state == 'reopen') {
        $state = 'open';
      }

      $vars = [
        'title' => $form_state->getValue('issue_title'),
        'state_event' => $state,
        'assignee_id' => $form_state->getValue('issue_assign'),
        'description' => $form_state->getValue('issue_description'),
        'due_date' => $form_state->getValue('issue_due_date'),
        'labels' => implode(',', $labels),
      ];

      if ($action == 'edit') {
        $issue = $form_state->get('issue');
        $this->editIssue($issue['id'], $vars);
        $url = Url::fromRoute('git_issues.issue.view', ['issueId' => $issue['id']]);
      }
      else {
        $vars['state'] = 'open';
        $this->addIssue($vars);
        $url = Url::fromRoute('git_issues.issues');
      }

      $form_state->setRedirectUrl($url);

    }
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
        'show_all' => $form_state->getValue('show_all'),
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
    $comments = $this->getIssueComments($issue['id']);
    $date = (new \DateTime($issue['updated_at']))->format('m/d/Y H:i');

    if (count($comments) > 0) {
      $author = $comments[0]['author']['name'];
    }
    else {
      $author = $issue['author']['name'];
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
   * Retrieves projects from GitLab API.
   *
   * {@inheritdoc}
   *
   * @return array
   *   Returns array with projects list.
   */
  public function getProjects() {
    return $this->doCall('get', $this->apiBaseUrl . 'projects/visible');
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
    return $this->doCall('get', $this->apiBaseUrl . "user");
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
    return $this->doCall('get', $this->apiBaseUrl . "projects/$this->projectId/members");
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
    return $this->doCall('get', $this->apiBaseUrl . "projects/$this->projectId/labels");
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
    return $this->doCall('post', $this->apiBaseUrl . "projects/$this->projectId/labels?" . http_build_query($vars));
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
    return $this->doCall('get', $this->apiBaseUrl . "projects/$this->projectId/issues/$issueId/notes");
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
    return $this->doCall('post', $this->apiBaseUrl . "projects/$this->projectId/issues?" . urldecode(http_build_query($vars)));
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
    return $this->doCall('put', $this->apiBaseUrl . "projects/$this->projectId/issues/$issueId?" . urldecode(http_build_query($vars)));
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
