<?php

/**
 * @file Contains \Drupal\powertagging\Form\PowerTaggingConfigConnectionForm.
 */

namespace Drupal\powertagging\Form;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\powertagging\Entity\PowerTaggingConfig;

/**
 * Class PowerTaggingConfigConnectionForm.
 *
 * @package Drupal\powertagging\Form
 */
class PowerTaggingConfigConnectionForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var PowerTaggingConfig $powertagging */
    $powertagging = $this->entity;

    if ($powertagging->isNew()) {
      $form['title'] = [
        '#type' => 'textfield',
        '#title' => t('Name'),
        '#description' => t("Name of the PowerTagging configuration."),
        '#size' => 35,
        '#maxlength' => 255,
        '#default_value' => $powertagging->getTitle(),
        '#required' => TRUE,
        '#validated' => TRUE,
      ];
    }
    else {
      $form['title'] = [
        '#type' => 'hidden',
        '#value' => $powertagging->getTitle(),
      ];
    }
    $settings = $powertagging->getConfig();

    $connection_overides = \Drupal::config('semantic_connector.settings')->get('override_connections');
    $overridden_values = [];
    if ($powertagging->isNew() && isset($connection_overides[$powertagging->id()])) {
      $overridden_values = $connection_overides[$powertagging->id()];
    }

    // Container: 1. Server settings.
    $form['server_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('1. Select the PoolParty server to use'),
    ];

    if (isset($overridden_values['connection_id'])) {
      $form['server_settings']['connection_id'] = [
        '#markup' => '<span class="semantic-connector-overridden-value">' . t('Warning: overridden by variable') . '</span>',
      ];
    }

    $connections = SemanticConnector::getConnectionsByType('pp_server');
    if (!empty($connections)) {
      $connection_options = [];
      /** @var \Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection $connection */
      foreach ($connections as $connection) {
        $credentials = $connection->getCredentials();
        $key = implode('|', array($connection->getTitle(), $connection->getUrl(), $credentials['username'], $credentials['password']));
        $connection_options[$key] = $connection->getTitle();
      }
      $form['server_settings']['load_connection'] = [
        '#type' => 'select',
        '#title' => t('Load an available PoolParty server'),
        '#options' => $connection_options,
        '#empty_option' => '',
        '#default_value' => '',
      ];
    }

    // Container: Connection details.
    $connection = $powertagging->getConnection();
    $form['server_settings']['connection_details'] = [
      '#type' => 'fieldset',
      '#title' => t('Connection details'),
    ];

    $form['server_settings']['connection_details']['connection_id'] = [
      '#type' => 'hidden',
      '#value' => $connection->id(),
    ];

    $form['server_settings']['connection_details']['server_title'] = [
      '#type' => 'textfield',
      '#title' => t('Server title'),
      '#description' => t('A short title for the server below.'),
      '#size' => 35,
      '#maxlength' => 60,
      '#default_value' => $connection->getTitle(),
      '#required' => TRUE,
    ];

    $form['server_settings']['connection_details']['url'] = [
      '#type' => 'url',
      '#title' => t('URL'),
      '#description' => t('URL, where the PoolParty server runs, without path information.'),
      '#size' => 35,
      '#maxlength' => 255,
      '#default_value' => $connection->getUrl(),
      '#required' => TRUE,
    ];

    $credentials = $connection->getCredentials();
    $form['server_settings']['connection_details']['credentials'] = [
      '#type' => 'details',
      '#title' => t('Credentials'),
      '#open' => FALSE,
    ];
    $form['server_settings']['connection_details']['credentials']['username'] = [
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#description' => t('Name of a user for the credentials.'),
      '#size' => 35,
      '#maxlength' => 60,
      '#default_value' => $credentials['username'],
    ];
    $form['server_settings']['connection_details']['credentials']['password'] = [
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#description' => t('Password of a user for the credentials.'),
      '#size' => 35,
      '#maxlength' => 128,
      '#default_value' => $credentials['password'],
    ];
    $form['server_settings']['health_check'] = [
      '#type' => 'button',
      '#value' => t('Health check'),
      '#ajax' => [
        'callback' => '::connectionTest',
        'wrapper' => 'health_info',
        'method' => 'replace',
        'effect' => 'slide',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Testing the connection...'),
        ],
      ],
    ];

    if ($powertagging->isNew()) {
      $markup = '<div id="health_info">' . t('Click to check if the server is available.') . '</div>';
    }
    else {
      $available = '<div id="health_info" class="available">' . t('The server is available.') . '</div>';
      $not_available = '<div id="health_info" class="not-available">' . t('The server is not available or the credentials are incorrect.') . '</div>';
      $markup = $connection->available() ? $available : $not_available;
    }
    $form['server_settings']['health_info'] = array(
      '#markup' => $markup,
    );

    // Container: 2. Project loading.
    $form['project_load'] = [
      '#type' => 'fieldset',
      '#title' => t('2. Load the projects'),
    ];
    $form['project_load']['load_projects'] = [
      '#type' => 'button',
      '#value' => t('Load projects'),
      '#ajax' => [
        'callback' => '::getProjects',
        'wrapper' => 'replace-selection-area',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Loading projects...'),
        ],
      ],
    ];

    // Container: 3. Project selection.
    $form['project_select'] = [
      '#type' => 'fieldset',
      '#title' => t('3. Select a project'),
      '#description' => t('Note: In case this list is still empty after clicking the "Load projects" button make sure that a connection to the PoolParty server can be established and check the rights of your selected user inside PoolParty.'),
      '#prefix' => '<div id="replace-selection-area">',
    ];

    // Get the project options for the currently configured PoolParty server.
    $connection = NULL;
    if($form_state->hasValue('url') && UrlHelper::isValid($form_state->getValue('url'), TRUE)) {
      // Create a new connection (without saving) with the current form data.
      $connection = SemanticConnector::getConnection('pp_server');
      $connection->setUrl($form_state->getValue('url'));
      $connection->setCredentials(array(
        'username' => $form_state->getValue('username'),
        'password' => $form_state->getValue('password'),
      ));
    }
    elseif (!$powertagging->isNew()) {
      $connection = $powertagging->getConnection();
    }

    // Get the project options for the currently configured PoolParty server.
    $projects = [];
    if (!is_null($connection)) {
      $projects = $connection->getApi('PPX')->getProjects();
    }

    $project_options = array();
    foreach ($projects as $project) {
      $project_options[$project['uuid']] = $project['label'];
    }
    if (!empty($project_options) && $form_state->hasValue('project') && !isset($project_options[$form_state->getValue('project')])) {
      $form_state->setValue('project', key($project_options));
    }

    // Get the project options for the currently configured PoolParty server.
    /*$project_options = array();
    if (!$powertagging->isNew()) {
      $projects = $connection->getApi('PPX')->getProjects();
      foreach ($projects as $project) {
        $project_options[$project['uuid']] = $project['label'];
      }
    }*/
    $form['project_select']['project'] = [
      '#type' => 'select',
      '#title' => t('Select a project'),
      '#prefix' => '<div id="projects-replace">',
      '#suffix' => '</div>',
      '#options' => $project_options,
      '#default_value' => (!$powertagging->isNew() ? $powertagging->getProjectId() : NULL),
      '#required' => TRUE,
      '#validated' => TRUE,
      '#ajax' => array(
        'callback' => '::getConceptSchemes',
        'wrapper' => 'concept-schemes-replace',
        'method' => 'replace',
        'effect' => 'fade',
      ),
    ];
    if (isset($overridden_values['project_id'])) {
      $form['project_select']['project']['#description'] = '<span class="semantic-connector-overridden-value">' . t('Warning: overridden by variable') . '</span>';
    }

    // Container: Project selection.
    $form['further_restrictions'] = array(
      '#type' => 'details',
      '#title' => t('4. Further restrictions'),
      '#description' => t('Note: A project has to be selected before any further restriction can be added.') . '<br />' . t('The restriction on the concept scheme level requires at least PoolParty version 6.2 to work properly.'),
      '#open' => (!$powertagging->isNew() && isset($settings['concept_scheme_restriction']) && !empty($settings['concept_scheme_restriction'])),
      '#suffix' => '</div>',
    );

    // Get the concept scheme options for the currently configured PoolParty server.
    $concept_schemes = [];
    if (!is_null($connection)) {
      if ($form_state->hasValue('url')) {
        $project_id = $form_state->hasValue('project') && !empty($project_options) && isset($project_options[$form_state->getValue('project')]) ? $form_state->getValue('project') : '';
      }
      else {
        $project_id = $powertagging->getProjectId();
      }

      if (!empty($project_id)) {
        $concept_schemes = $connection->getApi('PPT')
          ->getConceptSchemes($project_id);
      }
    }

    $concept_scheme_options = array();
    foreach ($concept_schemes as $concept_scheme) {
      $concept_scheme_options[$concept_scheme['uri']] = $concept_scheme['title'];
    }

    // configuration set admin page.
    $form['further_restrictions']['concept_scheme_restriction'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Filter by concept scheme'),
      '#description' => t('All concept schemes will be used if no checkbox is selected'),
      '#prefix' => '<div id="concept-schemes-replace">',
      '#suffix' => '</div>',
      '#options' => $concept_scheme_options,
      '#default_value' => (!$powertagging->isNew() && isset($settings['concept_scheme_restriction']) ? $settings['concept_scheme_restriction'] : []),
      '#validated' => TRUE,
    );

    $form['#attached'] = [
      'library' => [
        'powertagging/admin_area',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Only do project validation during the save-operation, not during
    // AJAX-requests like the health check of the server.
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#parents'][0] == 'submit') {
      // A project needs to be selected.
      if (empty($form_state->getValue('project'))) {
        $form_state->setErrorByName('project', t('Please select a project.'));
      }
      else {
        if ($form_state->hasValue('url') && UrlHelper::isValid($form_state->getValue('url'), TRUE)) {
          // Create a new connection (without saving) with the current form data.
          $connection = SemanticConnector::getConnection('pp_server');
          $connection->setUrl($form_state->getValue('url'));
          $connection->setCredentials([
            'username' => $form_state->getValue('username'),
            'password' => $form_state->getValue('password'),
          ]);

          $projects = $connection->getApi('PPX')->getProjects();
          $project_is_valid = FALSE;
          foreach ($projects as $project) {
            if ($project['uuid'] == $form_state->getValue('project')) {
              $project_is_valid = TRUE;
              break;
            }
          }
          if ($project_is_valid) {
            // Check if the selected concept schemes are available for this project.
            $concept_scheme_values = array_filter($form_state->getValue('concept_scheme_restriction'));
            if (!empty($concept_scheme_values)) {
              $concept_schemes = $connection->getApi('PPT')
                ->getConceptSchemes($form_state->getValue('project'));
              foreach ($concept_scheme_values as $concept_scheme_value) {
                $concept_scheme_exists = FALSE;
                foreach ($concept_schemes as $concept_scheme) {
                  if ($concept_scheme['uri'] == $concept_scheme_value) {
                    $concept_scheme_exists = TRUE;
                    break;
                  }
                }

                if (!$concept_scheme_exists) {
                  $form_state->setErrorByName('concept_scheme_restriction', t('At least one invalid concept scheme has been selected.'));
                  break;
                }
              }
            }
          }
          else {
            $form_state->setErrorByName('project', t('The selected project is not available on the given PoolParty server.'));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var PowerTaggingConfig $powertagging */
    $powertagging = $this->entity;

    // Always create a new connection, if URL and type are the same the old one
    // will be used anyway.
    $connection = SemanticConnector::createConnection('pp_server', $form_state->getValue('url'), $form_state->getValue('server_title'), [
      'username' => $form_state->getValue('username'),
      'password' => $form_state->getValue('password'),
    ]);
    $concept_scheme_values = array_values(array_filter($form_state->getValue('concept_scheme_restriction')));

    if ($powertagging->isNew()) {
      $powertagging->set('id', SemanticConnector::createUniqueEntityMachineName('powertagging', $powertagging->getTitle()));
    }
    $powertagging->set('connection_id', $connection->getId());
    $powertagging->set('project_id', $form_state->getValue('project'));

    // Add config changes.
    $settings = $powertagging->getConfig();
    $settings['concept_scheme_restriction'] = $concept_scheme_values;
    $powertagging->setConfig($settings);

    $status = $powertagging->save();
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('PowerTagging configuration %title has been created.', [
          '%title' => $powertagging->getTitle(),
        ]));
        break;

      default:
        drupal_set_message($this->t('PowerTagging configuration %title has been updated.', [
          '%title' => $powertagging->getTitle(),
        ]));
    }
    $form_state->setRedirectUrl(Url::fromRoute('entity.powertagging.edit_config_form', array('powertagging' => $powertagging->id())));
  }

  /**
   * Ajax callback function for checking if a new PoolParty server is available.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form_state object.
   *
   * @return array
   *   The output array to be rendered.
   */
  public function connectionTest(array &$form, FormStateInterface $form_state) {
    $available = '<div id="health_info" class="available"><div class="semantic-connector-led led-green" title="Service available"></div>' . t('The server is available.') . '</div>';
    $not_available = '<div id="health_info" class="not-available"><div class="semantic-connector-led led-red" title="Service NOT available"></div>' . t('The server is not available or the credentials are incorrect.') . '</div>';
    $markup = $not_available;

    if (!empty($form_state->getValue('url')) && UrlHelper::isValid($form_state->getValue('url'), TRUE)) {
      // Create a new connection (without saving) with the current form data.
      $connection = SemanticConnector::getConnection('pp_server');
      $connection->setUrl($form_state->getValue('url'));
      $connection->setCredentials([
        'username' => $form_state->getValue('username'),
        'password' => $form_state->getValue('password'),
      ]);

      $availability = $connection->getApi('PPX')->available();
      if (!empty($availability['message'])) {
        $markup = '<div id="health_info" class="not-available"><div class="semantic-connector-led led-red" title="Service NOT available"></div>' . $availability['message'] . '</div>';
      }
      else {
        $markup = $availability['success'] ? $available : $not_available;
      }
    }

    return [
      '#markup' => $markup,
    ];
  }

  /**
   * Ajax callback function to get a project select list for a given PoolParty
   * server connection configuration.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form_state object.
   *
   * @return array
   *   The selected form element containing the project objects for the current
   *   PoolParty server.
   */
  public function getProjects(array &$form, FormStateInterface $form_state) {
    $replaced_form = [];
    $replaced_form['project_select'] = $form['project_select'];
    $replaced_form['further_restrictions'] = $form['further_restrictions'];
    return $replaced_form;

    /*$projects_element = $form['project_select']['project'];

    $project_options = [];
    if (!empty($form_state->getValue('url')) && UrlHelper::isValid($form_state->getValue('url'), TRUE)) {
      // Create a new connection (without saving) with the current form data.
      $connection = SemanticConnector::getConnection('pp_server');
      $connection->setUrl($form_state->getValue('url'));
      $connection->setCredentials([
        'username' => $form_state->getValue('username'),
        'password' => $form_state->getValue('password'),
      ]);

      $projects = $connection->getApi('PPX')->getProjects();
      foreach ($projects as $project) {
        $project_options[$project['uuid']] = $project['label'];
      }
    }

    $projects_element['#options'] = $project_options;
    return $projects_element;*/
  }

  /**
   * Ajax callback function to get a concept schemes select list for a given
   * PoolParty server connection + project.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form_state object.
   *
   * @return array
   *   The select form element containing the concept scheme options for the
   *   currently selected project.
   */
  public function getConceptSchemes(&$form, FormStateInterface $form_state) {
    return $form['further_restrictions']['concept_scheme_restriction'];
  }
}
