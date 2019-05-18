<?php

/**
 * @file
 * Contains \Drupal\powertagging\Form\PowerTaggingConfigForm.
 */

namespace Drupal\powertagging\Form;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging\PowerTagging;
use Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfig;
use Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class PowerTaggingConfigForm.
 *
 * @package Drupal\powertagging\Form
 */
class PowerTaggingConfigForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var PowerTaggingConfig $powertagging_config */
    $powertagging_config = $this->entity;
    $config = $powertagging_config->getConfig();

    $connection = $powertagging_config->getConnection();
    /** @var \Drupal\semantic_connector\Api\SemanticConnectorPPXApi $ppx_api */
    $ppx_api = $connection->getApi('PPX');
    /** @var \Drupal\semantic_connector\Api\SemanticConnectorPPTApi $ppt_api */
    $ppt_api = $connection->getApi('PPT');
    $projects = $ppx_api->getProjects();

    // Set action links for bulk operations.
    $project_languages = array();
    foreach ($config['project']['languages'] as $pp_language) {
      if (!empty($pp_language)) {
        $project_languages[] = $pp_language;
      }
    }
    if (!empty($project_languages)) {
      // Make the dynamically added actions look like normal ones.
      $link_options = array(
        'attributes' => array(
          'class' => array(
            'button',
            'button-action',
            'button--primary',
            'button--small',
          ),
        ),
      );

      $action_tag = Url::fromRoute('entity.powertagging.tag_content', ['powertagging_config' => $powertagging_config->id()], $link_options);
      $action_update_taxonomy = Url::fromRoute('entity.powertagging.update_vocabulary', ['powertagging_config' => $powertagging_config->id()], $link_options);

      $form['action_links'] = [
        '#markup' => '
      <ul class="action-links">
          <li>' . Link::fromTextAndUrl(t('Tag content'), $action_tag)->toString() . '</li>
          <li>' . Link::fromTextAndUrl(t('Update vocabulary'), $action_update_taxonomy)->toString() . '</li>
      </ul>',
      ];
    }

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('Name of the PowerTagging configuration.'),
      '#size' => 35,
      '#maxlength' => 255,
      '#default_value' => $powertagging_config->getTitle(),
      '#required' => TRUE,
    ];

    // Add information about the connection.
    $form['pp_connection_markup'] = [
      '#markup' => $this->getConnectionInfo(),
    ];

    // Define the container for the vertical tabs.
    $form['settings'] = [
      '#type' => 'vertical_tabs',
    ];

    // Tab: Project settings.
    $form['project_settings'] = [
      '#type' => 'details',
      '#title' => t('Project settings'),
      '#group' => 'settings',
      '#tree' => TRUE,
    ];

    // Get the connected project.
    $projects = $ppx_api->getProjects();
    $project = NULL;
    if (!empty($projects)) {
      foreach ($projects as $project) {
        if ($project['uuid'] == $powertagging_config->getProjectId()) {
          break;
        }
      }
    }

    if (!is_null($project)) {
      $form['project_settings']['title'] = [
        '#type' => 'item',
        '#title' => t('Project name'),
        '#description' => $project['label'],
      ];

      // Language mapping.
      $project_language_options = array();
      foreach ($project['languages'] as $project_language) {
        $project_language_options[$project_language] = $project_language;
      }
      $form['project_settings']['languages'] = [
        '#type' => 'fieldset',
        '#title' => t('Map the Drupal languages with the PoolParty project languages')
      ];
      $states = [];
      // Go through the defined languages.
      foreach (\Drupal::languageManager()->getLanguages() as $language) {
        $form['project_settings']['languages'][$language->getId()] = [
          '#type' => 'select',
          '#title' => t('Drupal language: %language (@id)', ['%language' => $language->getName(), '@id' => $language->getId()]),
          '#description' => t('Select the PoolParty project language'),
          '#options' => $project_language_options,
          '#empty_option' => '',
          '#default_value' => !empty($config['project']['languages'][$language->getId()]) ? $config['project']['languages'][$language->getId()] : '',
        ];
        $states['#edit-project-settings-languages-' . $language->getId()] = ['value' => ''];
      }
      // Go through all locked languages ("Not specified" and "Not abblicable".
      foreach (\Drupal::languageManager()->getDefaultLockedLanguages() as $language) {
        $form['project_settings']['languages'][$language->getId()] = [
          '#type' => 'select',
          '#title' => t('Drupal language: %language', ['%language' => $language->getName()]),
          '#description' => t('Select the PoolParty project language'),
          '#options' => $project_language_options,
          '#empty_option' => '',
          '#default_value' => !empty($config['project']['languages'][$language->getId()]) ? $config['project']['languages'][$language->getId()] : '',
        ];
      }

      // Vocabulary selection.
      // Hidden field for the selecting the vocabulary.
      // It checks the availability of a language.
      $form['project_settings']['no_language_selected'] = [
        '#type' => 'checkbox',
        '#default_value' => FALSE,
        '#attributes' => ['class' => ['hidden']],
        '#states' => ['checked' => $states],
      ];
      $form['project_settings']['taxonomy_id'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'taxonomy_vocabulary',
        '#title' => t('Select or enter a new vocabulary'),
        '#default_value' => (!empty($config['project']['taxonomy_id']) ? Vocabulary::load($config['project']['taxonomy_id']) : ''),
        //'#validated' => TRUE,
        '#element_validate' => [[$this, 'validateTaxonomy']],
        '#states' => [
          'required' => ['#edit-project-settings-no-language-selected' => array('checked' => FALSE)],
          'disabled' => $states,
        ],
      ];

      // Ask if the vocabulary should be removed also if no language is
      // selected.
      if (!empty($config['project']['taxonomy_id'])) {
        $form['project_settings']['remove_taxonomy'] = array(
          '#type' => 'checkbox',
          '#title' => t('Remove the appropriate vocabulary. All terms and relations to this vocabulary will be removed also.'),
          '#states' => array(
            'visible' => $states,
          ),
        );
      }

      $form['project_settings']['mode'] = array(
        '#type' => 'radios',
        '#title' => t('PowerTagging mode'),
        '#options' => array(
          'annotation' => '<b>' . t('Annotation') . '</b> ' .  t('(use the whole thesaurus to tag the content)'),
          'classification' => '<b>' . t('Classification') . '</b> ' .  t('(categorize the content on the top concept level)'),
        ),
        '#default_value' => (isset($config['project']['mode']) ? $config['project']['mode'] : 'annotation'),
        '#required' => TRUE,
      );

      // Get the corpus options for the currently configured PoolParty server.
      $corpus_options = array();
      $corpora = $ppt_api->getCorpora($project['uuid']);
      foreach ($corpora as $corpus) {
        $corpus_options[$corpus['corpusId']] = $corpus['corpusName'];
      }
      // Get the default value for the corpus selection.
      $corpus_id = '';
      if (isset($config['project']['corpus_id']) && !empty($config['project']['corpus_id'])) {
        $corpus_id = $config['project']['corpus_id'];
      }
      $form['project_settings']['corpus_id'] = array(
        '#type' => 'select',
        '#title' => t('Select the corpus to use'),
        '#description' => t('Usage of a good corpus can improve your free terms considerably.'),
        '#options' => $corpus_options,
        '#default_value' => $corpus_id,
        "#empty_option" => !empty($corpus_options) ? '' : t('- No corpus available -'),
        '#states' => array(
          'visible' => array(':input[name="project_settings[mode]"]' => array('value' => 'annotation')),
        ),
      );

      if (isset($overridden_values['corpus_id'])) {
        $form['project_settings']['corpus_id']['#description'] = '<span class="semantic-connector-overridden-value">' . t('Warning: overridden by variable') . '</span>';
      }
    }
    else {
      $form['project_settings']['errors'] = array(
        '#type' => 'item',
        '#markup' => '<div class="messages warning">' . t('Either no connection can be established or there are no projects available for the given credentials.') . '</div>',
      );
    }

    // Tab: Global limit settings.
    $form['global_limit_settings'] = [
      '#type' => 'details',
      '#title' => t('Global limit settings'),
      '#group' => 'settings',
    ];
    static::addLimitsForm($form['global_limit_settings'], $config['limits']);

    // The most part of the global limits are only visible when PowerTagging is
    // used for annotation.
    $form['global_limit_settings']['concepts']['concepts_threshold']['#states'] = array(
      'visible' => array(':input[name="project_settings[mode]"]' => array('value' => 'annotation')),
    );
    $form['global_limit_settings']['freeterms']['#states'] = array(
      'visible' => array(':input[name="project_settings[mode]"]' => array('value' => 'annotation')),
    );

    $fields = $powertagging_config->getFields();
    if (!empty($fields)) {
      $form['global_limit_settings']['overwriting'] = array(
        '#type' => 'fieldset',
        '#title' => t('List of all content types with "PowerTagging Tags" fields'),
        '#description' => t('Select those content types which ones you want to overwrite the limits with the global limits defined above.'),
      );
      if (count($fields) > 1) {
        $form['global_limit_settings']['overwriting']['select_all_content_types'] = array(
          '#type' => 'checkbox',
          '#title' => t('Select all'),
          '#attributes' => array(
            'onclick' => 'jQuery("#edit-overwrite-content-types").find("input").prop("checked", jQuery(this).prop("checked"));',
          ),
        );
      }
      $form['global_limit_settings']['overwriting']['overwrite_content_types'] = array(
        '#type' => 'checkboxes',
        '#options' => $powertagging_config->renderFields('option_list', $fields),
        '#validated' => TRUE,
      );
    }

    // Tab: Data fetching settings.
    $properties = array(
      'skos:altLabel' => t('Alternative labels'),
      'skos:hiddenLabel' => t('Hidden labels'),
      'skos:scopeNote' => t('Scope notes'),
      'skos:related' => t('Related concepts'),
      'skos:exactMatch' => t('Exact matches'),
    );
    $form['data_properties_settings'] = array(
      '#type' => 'details',
      '#title' => t('Additional data'),
      '#group' => 'settings',
    );

    $form['data_properties_settings']['data_properties'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Select the properties that will be saved in addition to the taxonomy terms during the PowerTagging process'),
      '#description' => t('If you clear a checkbox, all data of that property will be deleted from the associated vocabulary.'),
      '#options' => $properties,
      '#default_value' => $config['data_properties'],
    );

    $form['concept_scheme_restriction'] = array(
      '#type' => 'value',
      '#value' => $config['concept_scheme_restriction'],
    );

    // Attach the libraries for the slider element.
    $form['#attached'] = [
      'library' => [
        'powertagging/widget',
      ],
    ];

    return $form;
  }

  // The validation handler for the vocabulary selection field.
  public function validateTaxonomy(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $taxonomy_name = trim(\Drupal\Component\Utility\Html::escape($element['#value']));
    // Do the custom element validation.
    EntityAutocomplete::validateEntityAutocomplete($element, $form_state, $complete_form);

    // Create a new vocabulary if required.
    if (!empty($taxonomy_name) && is_null($form_state->getValue(['project_settings', 'taxonomy_id']))) {
      // Remove potential Element validation errors.
      $form_errors = $form_state->getErrors();
      $form_state->clearErrors();
      foreach ($form_errors as $name => $message) {
        if ($name != 'project_settings][taxonomy_id') {
          $form_state->setErrorByName($name, $message);
        }
      }

      /** @var PowerTaggingConfig $powertagging_config */
      $powertagging_config = $this->entity;

      // Check if the new taxonomy already exists.
      $machine_name = PowerTagging::createMachineName($taxonomy_name);
      $taxonomy = Vocabulary::load($machine_name);

      if (!$taxonomy) {
        // Create the new vocabulary.
        $taxonomy = Vocabulary::create(array(
          'vid' => $machine_name,
          'machine_name' => $machine_name,
          'description' => substr(t('Automatically created by PowerTagging configuration') . ' "' . $powertagging_config->getTitle() . '".', 0, 128),
          'name' => $taxonomy_name,
        ));
        $taxonomy->save();
      }
      else {
        $form_state->setError($element, t('A taxonomy with the same machine name already exists.'));
      }

      $form_state->setValueForElement($element, $machine_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var PowerTaggingConfig $powertagging_config */
    $powertagging_config = $this->entity;

    $values = $form_state->getValues();
    $config = [
      'project' => [
        'languages' => $values['project_settings']['languages'],
        'taxonomy_id' => $values['project_settings']['taxonomy_id'],
        'corpus_id' => $values['project_settings']['corpus_id'],
        'mode' => $values['project_settings']['mode'],
      ],
      'limits' => [
        'concepts_per_extraction' => $values['concepts_per_extraction'],
        'concepts_threshold' => $values['concepts_threshold'],
        'freeterms_per_extraction' => $values['freeterms_per_extraction'],
        'freeterms_threshold' => $values['freeterms_threshold'],
      ],
      'concept_scheme_restriction' => $values['concept_scheme_restriction'],
    ];

    // Get the data properties for data fetching process.
    $config['data_properties'] = array_values(array_filter($values['data_properties']));

    // Delete the data for deselected properties
    $clear_fields = array_diff($form['data_properties_settings']['data_properties']['#default_value'], $config['data_properties']);
    if (!empty($clear_fields)) {
      $properties = array(
        'skos:altLabel' => 'field_alt_labels',
        'skos:hiddenLabel' => 'field_hidden_labels',
        'skos:scopeNote' => 'field_scope_notes',
        'skos:related' => 'field_related_concepts',
        'skos:exactMatch' => 'field_exact_match',
      );
      foreach ($clear_fields as $field) {
        $table = 'taxonomy_term__' . $properties[$field];
        \Drupal::database()->query('DELETE f
        FROM {' . $table . '} f
        LEFT JOIN {taxonomy_term_data} t ON f.entity_id = t.tid
        WHERE (
          t.vid = :vid
        )', [':vid' => $values['project_settings']['taxonomy_id']]);
      }
      drupal_flush_all_caches();
    }

    $powertagging_config->set('config', $config);

    // Set the vocabulary.
    if (!empty($values['project_settings']['taxonomy_id'])) {
      $vocabulary = Vocabulary::load($values['project_settings']['taxonomy_id']);
      // Delete vocabulary if it is desired.
      if (isset($values['project_settings']['remove_taxonomy']) && $values['project_settings']['remove_taxonomy']) {
        $vocabulary->delete();
      }
      else {
        self::addVocabularyFields($vocabulary);
      }
    }

    // Save PowerTagging configuration.
    $status = $powertagging_config->save();

    // Overwrite limits for all selected content types.
    if (isset($values['overwrite_content_types'])) {
      foreach ($values['overwrite_content_types'] as $content_type) {
        if ($content_type) {
          list($entity_type_id, $bundle, $field_type) = explode('|', $content_type);
          $field = [
            'entity_type_id' => $entity_type_id,
            'bundle' => $bundle,
            'field_type' => $field_type,
          ];
          $limits = [
            'concepts' => [
              'concepts_per_extraction' => $config['limits']['concepts_per_extraction'],
              'concepts_threshold' => $config['limits']['concepts_threshold'],
            ],
            'freeterms' => [
              'freeterms_per_extraction' => $config['limits']['freeterms_per_extraction'],
              'freeterms_threshold' => $config['limits']['freeterms_threshold'],
            ],
          ];
          $powertagging_config->updateField($field, 'limits', $limits);
        }
      }
    }

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('PowerTagging configuration %title has been created.', [
          '%title' => $powertagging_config->getTitle(),
        ]));
        break;

      default:
        drupal_set_message($this->t('PowerTagging configuration %title has been updated.', [
          '%title' => $powertagging_config->getTitle(),
        ]));
    }
    $form_state->setRedirectUrl(URL::fromRoute('entity.powertagging.collection'));
  }

  /**
   * Adds information about the connection.
   *
   * @return string
   *   Connection information.
   */
  protected function getConnectionInfo() {
    /** @var PowerTaggingConfig $powertagging_config */
    $powertagging_config = $this->entity;
    $settings = $powertagging_config->getConfig();
    /** @var SemanticConnectorPPServerConnection $connection */
    $connection = $powertagging_config->getConnection();

    // Get the project title of the currently configured project.
    $project_title = '<invalid project selected>';
    $pp_server_projects = $connection->getApi('PPX')->getProjects();
    foreach ($pp_server_projects as $pp_server_project) {
      if ($pp_server_project['uuid'] == $powertagging_config->getProjectId()) {
        $project_title = $pp_server_project['label'];
      }
    }

    // Add information about the connection.
    $connection_markup = '';
    // Check the PoolParty server version if required.
    if (\Drupal::config('semantic_connector.settings')->get('version_checking')) {
      $api_version_info = $connection->getVersionInfo('PPX');
      if (version_compare($api_version_info['installed_version'], $api_version_info['latest_version'], '<')) {
        $connection_markup .= '<div class="messages warning"><div class="message">';
        $connection_markup .= t('The connected PoolParty server is not up to date. You are currently running version %installed_version, upgrade to version %latest_version to enjoy the new features.', [
          '%installed_version' => $api_version_info['installed_version'],
          '%latest_version' => $api_version_info['latest_version'],
        ]);
        $connection_markup .= '</div></div>';
      }
    }
    $concept_scheme_labels = [];
    if (isset($settings['concept_scheme_restriction']) && !empty($settings['concept_scheme_restriction'])) {
      $concept_schemes = $powertagging_config->getConnection()->getApi('PPT')
        ->getConceptSchemes($powertagging_config->getProjectId());

      foreach ($concept_schemes as $concept_scheme) {
        if (in_array($concept_scheme['uri'], $settings['concept_scheme_restriction'])) {
          $concept_scheme_labels[] = $concept_scheme['title'];
        }
      }
    }

    $connection_markup .= '<p id="sonr-webmining-connection-info">' . t('Connected PoolParty server') . ': <b>' . $connection->getTitle() . ' (' . $connection->getUrl() . ')</b><br />';
    $connection_markup .= t('Selected project') . ': <b>' . $project_title . '</b><br />';
    if (!empty($concept_scheme_labels)) {
      $connection_markup .= t('Selected concept schemes restrictions') . ': <b>' . implode('</b>, <b>', $concept_scheme_labels) . '</b><br />';
    }
    $connection_markup .= Link::fromTextAndUrl(t('Change the connected PoolParty server or project'), Url::fromRoute('entity.powertagging.edit_form', ['powertagging' => $powertagging_config->id()]))->toString() . '</p>';

    return $connection_markup;
  }

  /**
   * Adds the form for the global limits.
   *
   * @param array $form
   *   The form where the global limits form will be added.
   * @param array $config
   *   The configuration data of a PowerTagging configuration.
   * @param boolean $tree
   *   The boolean value for the #tree attribute.
   */
  public static function addLimitsForm(array &$form, array $config, $tree = FALSE) {
    $form['concepts'] = array(
      '#type' => 'fieldset',
      '#title' => t('Concept / Category settings'),
      '#description' => t('Concepts are available in the thesaurus.'),
      '#tree' => $tree,
    );

    $form['concepts']['concepts_per_extraction'] = array(
      '#title' => t('Max concepts / categories per extraction'),
      '#type' => 'slider',
      '#default_value' => $config['concepts_per_extraction'],
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
      '#slider_style' => 'concept',
      '#slider_length' => '500px',
      '#description' => t('Maximum number of concepts (or categories when the PowerTagging mode is set to "Classification") to be displayed as a tagging result.'),
    );

    $form['concepts']['concepts_threshold'] = array(
      '#title' => t('Threshold level for the concepts'),
      '#type' => 'slider',
      '#default_value' => $config['concepts_threshold'],
      '#min' => 1,
      '#max' => 100,
      '#step' => 1,
      '#slider_style' => 'concept',
      '#slider_length' => '500px',
      '#description' => t('Only concepts with a minimum score of the chosen value will be displayed as a tagging result.'),
    );

    $form['freeterms'] = array(
      '#type' => 'fieldset',
      '#title' => t('Free term settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#description' => t('Free terms are extracted terms, which are not available in the thesaurus.'),
      '#tree' => $tree,
    );

    $form['freeterms']['freeterms_per_extraction'] = array(
      '#title' => t('Max free terms per extraction'),
      '#type' => 'slider',
      '#default_value' => $config['freeterms_per_extraction'],
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
      '#slider_style' => 'freeterm',
      '#slider_length' => '500px',
      '#description' => t('Maximum number of free terms for tagging.'),
    );

    $form['freeterms']['freeterms_threshold'] = array(
      '#title' => t('Threshold level for the free terms'),
      '#type' => 'slider',
      '#default_value' => $config['freeterms_threshold'],
      '#min' => 1,
      '#max' => 100,
      '#step' => 1,
      '#slider_length' => '500px',
      '#slider_style' => 'freeterm',
      '#description' => t('Only free terms with a minimum score of the chosen value will be used for tagging.') . '<br />' . t('WARNING: A threshold below 40 may reduce the quality of free term extractions!'),
    );
  }

  public static function addVocabularyFields(Vocabulary $vocabulary) {
    $fields = [
      'field_uri' => [
        'field_name' => 'field_uri',
        'type' => 'link',
        'label' => t('URI'),
        'description' => t('URI of the concept.'),
        'cardinality' => 1,
        'field_settings' => [],
        'required' => TRUE,
        'instance_settings' => [
          'link_type' => LinkItemInterface::LINK_GENERIC,
          'title' => DRUPAL_DISABLED,
        ],
        'widget' => [
          'type' => 'link_default',
          'weight' => 3,
        ],
      ],
      'field_alt_labels' => [
        'field_name' => 'field_alt_labels',
        'type' => 'string',
        'label' => t('Alternative labels'),
        'description' => t('A list of synonyms.'),
        'cardinality' => -1,
        'field_settings' => [
          'max_length' => 1024,
        ],
        'required' => FALSE,
        'instance_settings' => [],
        'widget' => [
          'type' => 'string_textfield',
          'weight' => 4,
        ],
      ],
      'field_hidden_labels' => [
        'field_name' => 'field_hidden_labels',
        'type' => 'string',
        'label' => t('Hidden labels'),
        'description' => t('A list of secondary variants of this term.'),
        'cardinality' => -1,
        'field_settings' => [
          'max_length' => 1024,
        ],
        'required' => FALSE,
        'instance_settings' => [],
        'widget' => [
          'type' => 'string_textfield',
          'weight' => 5,
        ],
      ],
      'field_scope_notes' => [
        'field_name' => 'field_scope_notes',
        'type' => 'string_long',
        'label' => t('Scope notes'),
        'description' => t('An information about the scope of a concept'),
        'cardinality' => -1,
        'required' => FALSE,
        'instance_settings' => [],
        'field_settings' => [],
        'widget' => array(
          'type' => 'string_textarea',
          'weight' => 6,
        ),
      ],
      'field_related_concepts' => [
        'field_name' => 'field_related_concepts',
        'type' => 'link',
        'label' => t('Related concepts'),
        'description' => t('URIs to related concepts'),
        'cardinality' => -1,
        'required' => FALSE,
        'instance_settings' => [
          'link_type' => LinkItemInterface::LINK_GENERIC,
          'title' => DRUPAL_DISABLED,
        ],
        'field_settings' => [],
        'widget' => array(
          'type' => 'link_default',
          'weight' => 7,
        ),
      ],
      'field_exact_match' => [
        'field_name' => 'field_exact_match',
        'type' => 'link',
        'label' => t('Exact matches'),
        'description' => t('URIs which show to the same concept at a different data source.'),
        'cardinality' => -1,
        'field_settings' => [],
        'required' => FALSE,
        'instance_settings' => [
          'link_type' => LinkItemInterface::LINK_GENERIC,
          'title' => DRUPAL_DISABLED,
        ],
        'widget' => [
          'type' => 'link_default',
          'weight' => 8,
        ],
      ],
    ];
    foreach ($fields as $field) {
      self::createVocabularyField($field);
      self::addFieldtoVocabulary($field, $vocabulary);

      // Set the widget data.
      entity_get_form_display('taxonomy_term', $vocabulary->id(), 'default')
        ->setComponent($field['field_name'], $field['widget'])
        ->save();
    }
  }

  protected static function createVocabularyField(array $field) {
    if (is_null(FieldStorageConfig::loadByName('taxonomy_term', $field['field_name']))) {
      $new_field = [
        'field_name' => $field['field_name'],
        'type' => $field['type'],
        'entity_type' => 'taxonomy_term',
        'cardinality' => $field['cardinality'],
        'settings' => $field['field_settings'],
      ];
      FieldStorageConfig::create($new_field)->save();
    }
  }

  protected static function addFieldtoVocabulary(array $field, Vocabulary $vocabulary) {
    if (is_null(FieldConfig::loadByName('taxonomy_term', $vocabulary->id(), $field['field_name']))) {
      $instance = [
        'field_name' => $field['field_name'],
        'entity_type' => 'taxonomy_term',
        'bundle' => $vocabulary->id(),
        'label' => $field['label'],
        'description' => $field['description'],
        'required' => $field['required'],
        'settings' => $field['instance_settings'],
      ];
      FieldConfig::create($instance)->save();
    }
  }
}