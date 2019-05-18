<?php

/**
 * @file
 * The main class of the PoolParty Taxonomy Manager.
 */

namespace Drupal\pp_taxonomy_manager;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfig;
use Drupal\semantic_connector\Api\SemanticConnectorPPTApi;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermStorage;

/**
 * A collection of static functions offered by the PoolParty Taxonomy Manager module.
 */
class PPTaxonomyManager {

  protected static $instance;
  protected $config;

  /**
   * Constructor of the PoolParty Taxonomy Manager class.
   *
   * @param $config PPTaxonomyManagerConfig
   *   The configuration of the PoolParty Taxonomy Manager.
   */
  protected function __construct($config) {
    $this->config = $config;
  }

  /**
   * Get a smart-glossary-instance (Singleton).
   *
   * @param $config PPTaxonomyManagerConfig
   *   The configuration of the PoolParty Taxonomy Manager.
   *
   * @return PPTaxonomyManager
   *   The PoolParty Taxonomy Manager instance.
   */
  public static function getInstance($config) {
    if (!isset(self::$instance)) {
      $object_name = __CLASS__;
      self::$instance = new $object_name($config);
    }
    return self::$instance;
  }

  /**
   * Create a new PP Taxonomy Manager configuration.
   *
   * @param string $title
   *   The title of the configuration.
   * @param string $project_id
   *   The ID of the project
   * @param string $connection_id
   *   The ID of Semantic Connector connection
   * @param array $config
   *   The config of the PP Taxonomy Manager configuration as an array.
   *
   * @return PPTaxonomyManagerConfig
   *   The new PP Taxonomy Manager configuration.
   */
  public static function createConfiguration($title, $project_id, $connection_id, array $config = array()) {
    $configuration = PPTaxonomyManagerConfig::create();
    $configuration->set('id', SemanticConnector::createUniqueEntityMachineName('pp_taxonomy_manager', $title));
    $configuration->setTitle($title);
    $configuration->setProjectID($project_id);
    $configuration->setConnectionId($connection_id);
    $configuration->setConfig($config);
    $configuration->save();

    return $configuration;
  }

  /**
   * Updates an existing connection between a taxonomy and a PP concept scheme.
   *
   * @param int $vid
   *   The taxonomy ID.
   * @param string $scheme_uri
   *   The concept scheme URI.
   * @param array $languages
   *   An array of language mappings between Drupal and PoolParty project
   *   languages.
   * @param array $data_properties
   *   An array of SKOS properties to be fetched.
   */
  public function updateConnection($vid, $scheme_uri, $languages, $data_properties) {
    $this->addConnection($vid, $scheme_uri, $languages, $data_properties);
  }

  /**
   * Adds a new connection between a taxonomy and a PoolParty concept scheme.
   *
   * @param int $vid
   *   The taxonomy ID.
   * @param string $scheme_uri
   *   The concept scheme URI.
   * @param array $languages
   *   An array of language mappings between Drupal and PoolParty project
   *   languages.
   * @param array $data_properties
   *   An array of SKOS properties to be fetched.
   */
  public function addConnection($vid, $scheme_uri, $languages, $data_properties) {
    $settings = $this->config->getConfig();
    $settings['taxonomies'][$vid] = $scheme_uri;
    $settings['languages'][$vid] = $languages;
    $settings['data_properties'][$vid] = $data_properties;
    $this->config->setConfig($settings);
    $this->config->save();
  }

  /**
   * Deletes a connection between a taxonomy and a PoolParty concept scheme.
   *
   * @param int $vid
   *   The taxonomy ID.
   */
  public function deleteConnection($vid) {
    $settings = $this->config->getConfig();
    unset($settings['taxonomies'][$vid]);
    unset($settings['languages'][$vid]);
    unset($settings['data_properties'][$vid]);
    $this->config->setConfig($settings);
    $this->config->save();
  }

  /**
   * Creates a new Drupal taxonomy.
   *
   * @param array $root_object
   *   Either a PoolParty concept scheme or a PoolParty project.
   * @param string $taxonomy_name
   *   The name of the taxonomy to create If NULL is given the title of the
   *   concept scheme gets used (default).
   *
   * @return Vocabulary
   *   The created taxonomy.
   */
  public function createTaxonomy(array $root_object, $taxonomy_name = '') {
    $taxonomy_name = trim(\Drupal\Component\Utility\Html::escape($taxonomy_name));
    if (empty($taxonomy_name)) {
      $taxonomy_name = $root_object['title'];
    }

    // Check if the new taxonomy already exists.
    $machine_name = self::createMachineName($taxonomy_name);
    $taxonomy = Vocabulary::load($machine_name);

    if (!$taxonomy) {
      // Create the new taxonomy.
      $taxonomy = Vocabulary::create(array(
        'vid' => $machine_name,
        'machine_name' => $machine_name,
        'description' => substr(t('Automatically created by PoolParty Taxonomy Manager.') . ' ' . (isset($root_object['description']) ? $root_object['description'] : ((isset($root_object['descriptions']) && !empty($root_object['descriptions'])) ? ' ' . $root_object['descriptions'][0] : '')), 0, 128),
        'name' => $taxonomy_name,
      ));
      $taxonomy->save();

      drupal_set_message(t('Vocabulary %taxonomy successfully created.', array('%taxonomy' => $taxonomy_name)));
      \Drupal::logger('pp_taxonomy_manager')->notice('Vocabulary created: %taxonomy (VID = %vid)', array(
        '%taxonomy' => $taxonomy_name,
        '%vid' => $taxonomy->id(),
      ));
    }

    return $taxonomy;
  }

  /**
   * Set the correct translation mode for the Drupal taxonomy.
   *
   * @param Vocabulary $vocabulary
   *   A Drupal taxonomy.
   * @param array $languages
   *   An array of languages:
   *    key = Drupal language
   *    value = PoolParty language.
   *
   * @return boolean
   *   TRUE if the translation mode had to be changed, FALSE if not.
   */
  public function enableTranslation($vocabulary, $languages) {
    if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
      $language_count = count($languages);

      // Make the taxonomy translatable if the translation module for taxonomies
      // is installed and more than one language is selected.
      if ($language_count > 1 && !\Drupal::service('content_translation.manager')->isEnabled('taxonomy_term', $vocabulary->id())) {
        \Drupal::service('content_translation.manager')->setEnabled('taxonomy_term', $vocabulary->id(), TRUE);
        \Drupal::entityTypeManager()->clearCachedDefinitions();
        \Drupal::service('router.builder')->setRebuildNeeded();
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Adds additional fields to a specific taxonomy term if not exists.
   *
   * @param Vocabulary $vocabulary
   *   A Drupal taxonomy object.
   */
  public function adaptTaxonomyFields(Vocabulary $vocabulary) {
    $fields = self::taxonomyFields();
    foreach ($fields as $field) {
      $this->createVocabularyField($field);
      $this->addFieldtoVocabulary($field, $vocabulary);

      // Set the widget data.
      entity_get_form_display('taxonomy_term', $vocabulary->id(), 'default')
        ->setComponent($field['field_name'], $field['widget'])
        ->save();
    }
  }

  /**
   * Creates a new field if not exists.
   *
   * @param array $field
   *   The field that should be created.
   */
  protected function createVocabularyField(array $field) {
    if (is_null(FieldStorageConfig::loadByName('taxonomy_term', $field['field_name']))) {
      $new_field = [
        'field_name' => $field['field_name'],
        'type' => $field['type'],
        'entity_type' => 'taxonomy_term',
      ];
      if (isset($field['field_settings'])) {
        $new_field['settings'] = $field['field_settings'];
      }
      if (isset($field['cardinality'])) {
        $new_field['cardinality'] = $field['cardinality'];
      }
      FieldStorageConfig::create($new_field)->save();
    }
  }

  /**
   * Adds a field to a specific taxonomy term if not exists.
   *
   * @param array $field
   *   The field that should be added.
   * @param Vocabulary $vocabulary
   *   The taxonomy at which the field should be added.
   */
  protected function addFieldtoVocabulary(array $field, Vocabulary $vocabulary) {
    if (is_null(FieldConfig::loadByName('taxonomy_term', $vocabulary->id(), $field['field_name']))) {
      $instance = [
        'field_name' => $field['field_name'],
        'entity_type' => 'taxonomy_term',
        'bundle' => $vocabulary->id(),
        'label' => $field['label'],
        'description' => $field['description'],
        'required' => $field['required'],
        'settings' => isset($field['instance_settings']) ? $field['instance_settings'] : [],
      ];
      FieldConfig::create($instance)->save();
    }
  }

  /**
   * Creates a new concept scheme on the specified PoolParty server.
   *
   * @param Vocabulary $vocabulary
   *   A Drupal taxonomy object.
   * @param string $scheme_title
   *   The title of the concept scheme to create. If NULL is given the name of
   *   the taxonomy gets used (default).
   *
   * @return string
   *   The URI of the new concept scheme.
   */
  public function createConceptScheme(Vocabulary $vocabulary, $scheme_title = '') {
    $scheme_title = trim(\Drupal\Component\Utility\Html::escape($scheme_title));
    if (empty($scheme_title)) {
      $scheme_title = $vocabulary->label();
    }

    $description = 'Automatically created by Drupal. ' . $vocabulary->getDescription();
    /** @var SemanticConnectorPPTApi $ppt */
    $ppt = $this->config->getConnection()->getAPI('PPT');
    $scheme_uri = $ppt->createConceptScheme($this->config->getProjectId(), $scheme_title, $description);
    drupal_set_message(t('Concept scheme %scheme successfully created.', array('%scheme' => $scheme_title)));
    \Drupal::logger('pp_taxonomy_manager')->notice('Concept scheme created: %scheme (URI = %uri)', array(
      '%scheme' => $scheme_title,
      '%uri' => $scheme_uri,
    ));

    return $scheme_uri;
  }

  /**
   * Creates a new project on the specified PoolParty server.
   *
   * @param Vocabulary $taxonomy
   *   A Drupal taxonomy object.
   * @param string $project_title
   *   The title of the project to create. If NULL is given the name of
   *   the taxonomy gets used (default).
   * @param string $default_language
   *   The language code of the language to use as default for the new project
   *   in PoolParty.
   * @param array $available_languages
   *   A complete array of languages available for the new PoolParty project;
   *   If this array is empty only the default language will be used.
   *
   * @return string
   *   The ID of the new project.
   */
  public function createProject(Vocabulary $taxonomy, $project_title = '', $default_language = 'en', array $available_languages = array()) {
    $project_title = trim(\Drupal\Component\Utility\Html::escape($project_title));
    if (empty($project_title)) {
      $project_title = $taxonomy->label();
    }

    if (empty($available_languages)) {
      $available_languages = array($default_language);
    }

    $description = 'Automatically created by Drupal. ' . $taxonomy->getDescription();
    /* @var $ppt SemanticConnectorPPTApi */
    $ppt = $this->config->getConnection()->getAPI('PPT');

    $project_id = $ppt->createProject($project_title, $default_language, array('Public'), array('description' => $description, 'availableLanguages' => $available_languages));
    drupal_set_message(t('Project %project successfully created.', array('%project' => $project_title)));
    \Drupal::logger('pp_taxonomy_manager')->notice('Project created: %project (ID = %id)', array(
      '%project' => $project_title,
      '%id' => $project_id,
    ));

    return $project_id;
  }

  /**
   * Creates a batch for exporting all terms of a taxonomy.
   *
   * @param Vocabulary $vocabulary
   *   A Drupal taxonomy object.
   * @param string $root_uri
   *   A concept scheme URI or project ID.
   * @param array $languages
   *   An array of languages:
   *    key = Drupal language
   *    value = PoolParty language.
   * @param int $terms_per_request
   *   Count of taxonomy terms per http request.
   */
  public function exportTaxonomyTerms(Vocabulary $vocabulary, $root_uri, $languages, $terms_per_request) {
    $start_time = time();

    // Configure the batch data.
    $batch = array(
      'title' => t('Exporting taxonomy %name ...', array('%name' => $vocabulary->label())),
      'operations' => array(),
      'init_message' => t('Starting with the export of the taxonomy terms.'),
      'progress_message' => t('Processed @current out of @total.'),
      'finished' => array('\Drupal\pp_taxonomy_manager\PPTaxonomyManagerBatches', 'exportTermsFinished'),
    );

    // Get the taxonomy tree for the default language.
    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $tree = \Drupal::service('entity_type.manager')
      ->getStorage("taxonomy_term")
      ->loadTree($vocabulary->id(), 0, NULL, TRUE);

    // Set additional data.
    $count = count($tree);
    $info = array(
      'total' => $count,
      'start_time' => $start_time,
    );

    // Enable the translation for the taxonomy if required.
    $this->enableTranslation($vocabulary, $languages);

    // Set the export operations.
    for ($i = 0; $i < $count; $i += $terms_per_request) {
      $terms = array_slice($tree, $i, $terms_per_request);
      $tids = array();
      /** @var Term $term */
      foreach ($terms as $term) {
        $tids[] = $term->id();
      }

      $batch['operations'][] = array(
        array('\Drupal\pp_taxonomy_manager\PPTaxonomyManagerBatches', 'exportTerms'),
        array(
          $this,
          $tids,
          $default_language,
          $languages[$default_language],
          $root_uri,
          $info,
        ),
      );
    }

    // Set the export of related concepts operations.
    for ($i = 0; $i < $count; $i += $terms_per_request) {
      $terms = array_slice($tree, $i, $terms_per_request);
      $tids = array();
      /** @var Term $term */
      foreach ($terms as $term) {
        $tids[] = $term->id();
      }

      $batch['operations'][] = array(
        array('\Drupal\pp_taxonomy_manager\PPTaxonomyManagerBatches', 'exportRelations'),
        array(
          $this,
          $tids,
          $info,
        ),
      );
    }

    // Set the update hash table operations after the export of all terms.
    for ($i = 0; $i < $count; $i += $terms_per_request) {
      $terms = array_slice($tree, $i, $terms_per_request);
      $tids = array();
      /** @var Term $term */
      foreach ($terms as $term) {
        $tids[] = $term->id();
      }

      $batch['operations'][] = array(
        array('\Drupal\pp_taxonomy_manager\PPTaxonomyManagerBatches', 'updateTermHashes'),
        array(
          $this,
          $tids,
          $info
        ),
      );
    }

    // Set the export translation operations.
    unset($languages[$default_language]);
    if (!empty($languages)) {
      foreach ($languages as $drupal_lang => $pp_lang) {
        $count = count($tree);
        $info = array(
          'total' => $count,
          'start_time' => $start_time,
        );

        for ($i = 0; $i < $count; $i += $terms_per_request) {
          $terms = array_slice($tree, $i, $terms_per_request);
          $tids = array();
          /** @var Term $term */
          foreach ($terms as $term) {
            $tids[] = $term->id();
          }

          $batch['operations'][] = array(
            array('\Drupal\pp_taxonomy_manager\PPTaxonomyManagerBatches', 'exportTermTranslations'),
            array(
              $this,
              $tids,
              $drupal_lang,
              $pp_lang,
              $info
            ),
          );
        }
      }
    }

    // Set the log operation.
    $batch['operations'][] = array(
      array('\Drupal\pp_taxonomy_manager\PPTaxonomyManagerBatches', 'saveVocabularyLog'),
      array(
        $this,
        $vocabulary->id(),
        $info
      ),
    );

    // Start the batch.
    batch_set($batch);
  }

  /**
   * Batch process method for exporting taxonomy terms into a PoolParty server.
   *
   * @param int[] $tids
   *   The IDs of the taxonomy terms that are to be exported.
   * @param string $drupal_lang
   *   The language of the taxonomy terms that are to be exported.
   * @param string $pp_lang
   *   The language of the concept that are to be created.
   * @param array $context
   *   The batch context to transmit data between different calls.
   */
  public function exportBatch(array $tids, $drupal_lang, $pp_lang, array &$context) {
    /** @var SemanticConnectorPPTApi $ppt */
    $ppt = $this->config->getConnection()->getAPI('PPT');

    $fields = self::taxonomyFields();
    $settings = $this->config->getConfig();
    $exported_terms = &$context['results']['exported_terms'];
    $project_id = ($settings['root_level'] == 'conceptscheme' ? $this->config->getProjectId() : $exported_terms[0]['uri']);

    $terms = Term::loadMultiple($tids);
    /** @var Term $term */
    foreach ($terms as $term) {
      // Create an array of parent IDs
      /** @var TermStorage $term_storage */
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $parents = $term_storage->loadParents($term->id());
      $parent_tids = array();
      if (empty($parents)) {
        $parent_tids[] = 0;
      }
      else {
        /** @var Term $parent */
        foreach ($parents as $parent) {
          $parent_tids[] = $parent->id();
        }
      }

      // Create new concept.
      $created = FALSE;
      if (!isset($exported_terms[$term->id()])) {
        foreach ($parent_tids as $parent_tid) {
          if (isset($exported_terms[$parent_tid])) {
            // Create new concept.
            if (!$created) {
              if ($settings['root_level'] == 'project' && $parent_tid == 0) {
                $uri = $ppt->createConceptScheme($project_id, $term->getName(), (!empty($term->getDescription()) ? $term->getDescription() : ''));
              }
              else {
                $uri = $ppt->createConcept($project_id, $term->getName(), $exported_terms[$parent_tid]['uri']);
                // Add definition, alt labels, hidden labels and custom properties
                // if required.
                if (!empty($term->getDescription())) {
                  $raw_description = str_replace(array("\r", "\n"), '', strip_tags($term->getDescription()));
                  if (!empty($raw_description)) {
                    $ppt->addLiteral($project_id, $uri, 'definition', $raw_description, $pp_lang);
                  }
                }

                // Add all other labels and data.
                foreach ($fields as $field_id => $field_schema) {
                  if (!isset($field_schema['push_key'])) {
                    continue;
                  }
                  if (isset($term->{$field_id})) {
                    $custom_field_values = $term->{$field_id}->getValue();
                    if (isset($field_schema['cardinality']) && $field_schema['cardinality'] != 1) {
                      foreach ($custom_field_values as $value) {
                        $value = trim(isset($value['value']) ? $value['value'] : (isset($value['uri']) ? $value['uri'] : ''));
                        if (!empty($value)) {
                          $ppt->addLiteral($project_id, $uri, $field_schema['push_key'], $value, $pp_lang);
                        }
                      }
                    }
                    else {
                      $char = isset($field_schema['merge_char']) ? $field_schema['merge_char'] : ',';
                      $field_value = (isset($custom_field_values[0]['value']) ? $custom_field_values[0]['value'] : (isset($custom_field_values[0]['uri']) ? $custom_field_values[0]['uri'] : ''));
                      if ($char == ' ') {
                        $values = [$field_value];
                      }
                      else {
                        $values = explode($char, $field_value);
                      }
                      foreach ($values as $value) {
                        $value = trim($value);
                        if (!empty($value)) {
                          $ppt->addLiteral($project_id, $uri, $field_schema['push_key'], $value, $pp_lang);
                        }
                      }
                    }
                  }
                }
              }

              // Save the old URI for any relations between concepts on URI
              // basis.
              $old_uri = NULL;
              if ($term->hasField('field_uri') && $term->get('field_uri')->count()) {
                $old_uri = $term->get('field_uri')->getString();
              }

              // Update term with the new URI.
              $term->get('field_uri')->setValue($uri);
              $term->save();

              $exported_terms[$term->id()] = array(
                'uri' => $uri,
                'old_uri' => $old_uri,
                'parents' => array($parent_tid),
                'drupalLang' => $drupal_lang,
                'ppLang' => $pp_lang,
                'hash' => FALSE,
              );
              \Drupal::logger('pp_taxonomy_manager')->notice('Concept created: %name (URI = %uri)', array(
                '%name' => $term->getName(),
                '%uri' => $uri,
              ));
              $created = TRUE;
            }
            // Add additional parents.
            else {
              $relation_type = ($settings['root_level'] != 'project' || !in_array(0, $exported_terms[$parent_tid]['parents'])) ? 'broader' : 'topConceptOf';
              $uri = $exported_terms[$term->id()]['uri'];
              $ppt->addRelation($project_id, $uri, $exported_terms[$parent_tid]['uri'], $relation_type);
              $exported_terms[$term->id()]['parents'][] = $parent_tid;
            }
          }
        }
      }
      // Add missing parents to the concept.
      else {
        foreach ($parent_tids as $parent_tid) {
          $relation_type = ($settings['root_level'] != 'project' || !in_array(0, $exported_terms[$parent_tid]['parents'])) ? 'broader' : 'topConceptOf';
          if (isset($exported_terms[$parent_tid]) && !in_array($parent_tid, $exported_terms[$term->id()]['parents'])) {
            $uri = $exported_terms[$term->id()]['uri'];
            $ppt->addRelation($project_id, $uri, $exported_terms[$parent_tid]['uri'], $relation_type);
            $exported_terms[$term->id()]['parents'][] = $parent_tid;
          }
        }
      }
      $context['results']['processed']++;
    }
  }

  /**
   * Batch process method for exporting relations data.
   *
   * @param int[] $tids
   *   The IDs of the taxonomy terms in the default language.
   * @param array $context
   *   The batch context to transmit data between different calls.
   */
  public function exportRelationsBatch(array $tids, array &$context) {
    /** @var $ppt SemanticConnectorPPTApi */
    $ppt = $this->config->getConnection()->getAPI('PPT');
    $settings = $this->config->getConfig();
    $exported_terms = &$context['results']['exported_terms'];
    $project_id = ($settings['root_level'] == 'conceptscheme' ? $this->config->getProjectId() : $exported_terms[0]['uri']);

    $terms = Term::loadMultiple($tids);
    /** @var Term $term */
    foreach ($terms as $term) {
      if (isset($exported_terms[$term->id()]) && !empty($term->field_uri)) {
        $source = $term->get('field_uri')->getValue();
        $related_concepts = $term->get('field_related_concepts')->getValue();
        if (!empty($related_concepts)) {
          $new_relation_uris = [];
          foreach ($related_concepts as $uri) {
            // Search for the new URI of the relation.
            foreach ($exported_terms as $exported_term) {
              if (isset($exported_term['old_uri']) && $exported_term['old_uri'] == $uri['uri'] && isset($exported_term['uri'])) {
                $new_relation_uris[] = $exported_term['uri'];
                $ppt->addRelation($project_id, $source[0]['uri'], $exported_term['uri'], 'skos:related');
              }
            }
          }

          // Update term with the new related concept URIs.
          $term->get('field_related_concepts')->setValue($new_relation_uris);
          $term->save();
        }
      }
      $context['results']['related_concepts_processed']++;
    }
  }

  /**
   * Batch process function for updating the hash table after the export.
   *
   * @param int[] $tids
   *   The IDs of the taxonomy terms that are to be exported.
   * @param array $info
   *   An associative array of information about the batch process.
   * @param array $context
   *   The batch context to transmit data between different calls.
   */
  public function updateHashBatch(array $tids, array $info, array &$context) {
    /** @var SemanticConnectorPPTApi $ppt */
    $ppt = $this->config->getConnection()->getAPI('PPT');

    $settings = $this->config->getConfig();
    $exported_terms = &$context['results']['exported_terms'];
    $project_id = ($settings['root_level'] == 'conceptscheme' ? $this->config->getProjectId() : $exported_terms[0]['uri']);

    $terms = Term::loadMultiple($tids);
    /** @var Term $term */
    foreach ($terms as $term) {
      if (isset($exported_terms[$term->id()]) && !$exported_terms[$term->id()]['hash']) {
        /** @var TermStorage $term_storage */
        $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        $parents = $term_storage->loadParents($term->id());
        $parent_tids = array();
        if (empty($parents)) {
          $parent_tids[] = 0;
        }
        else {
          /** @var Term $parent */
          foreach ($parents as $parent) {
            $parent_tids[] = $parent->id();
          }
        }

        // Add hash data to the database.
        $data = $exported_terms[$term->id()];
        // The term is a concept scheme.
        if ($settings['root_level'] == 'project' && $parent_tids == array(0)) {
          // Create an array of parent IDs
          $concept = [];
          $schemes = $ppt->getConceptSchemes($project_id, $data['ppLang']);
          foreach ($schemes as $scheme) {
            if ($scheme['uri'] == $data['uri']) {
              $concept = $scheme;
            }
          }
        }
        // The term is a concept.
        else {
          $concept = $ppt->getConcept($project_id, $data['uri'], $this->skosProperties(), $data['ppLang']);
        }
        if (!empty($concept)) {
          $concept['drupalLang'] = $data['drupalLang'];
          $concept['ppLang'] = $data['ppLang'];

          $uri_lang = $this->getUri($concept);
          $hash = $this->hash($concept);
          $this->addHashData($term, $data['ppLang'], $uri_lang, $hash, $info['start_time']);

          $exported_terms[$term->id()]['hash'] = TRUE;
        }
        $context['results']['hash_update_processed']++;
      }
    }
  }

  /**
   * Batch process method for exporting taxonomy terms into a PoolParty server.
   *
   * @param int[] $tids
   *   The IDs of the taxonomy terms that are to be exported.
   * @param string $drupal_lang
   *   The language of the taxonomy terms that are to be exported.
   * @param string $pp_lang
   *   The language of the concept that are to be created.
   * @param array $info
   *   An associative array of information about the batch process.
   * @param array $context
   *   The batch context to transmit data between different calls.
   */
  public function exportTranslationsBatch(array $tids, $drupal_lang, $pp_lang, array $info, array &$context) {
    /** @var SemanticConnectorPPTApi $ppt */
    $ppt = $this->config->getConnection()->getAPI('PPT');

    $fields = self::taxonomyFields();
    $settings = $this->config->getConfig();
    $exported_terms = $context['results']['exported_terms'];
    //$default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $project_id = ($settings['root_level'] == 'conceptscheme' ? $this->config->getProjectId() : $exported_terms[0]['uri']);

    $terms = Term::loadMultiple($tids);
    /** @var Term $term */
    foreach ($terms as $term) {
      // Check if the term with the default language is already exported.
      if (isset($exported_terms[$term->id()]) && $term->hasTranslation($drupal_lang)) {
        // Get the translated version of the taxonomy term.
        $term = $term->getTranslation($drupal_lang);

        /** @var TermStorage $term_storage */
        $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        $parents = $term_storage->loadParents($term->id());
        $parent_tids = array();
        if (empty($parents)) {
          $parent_tids[] = 0;
        }
        else {
          /** @var Term $parent */
          foreach ($parents as $parent) {
            $parent_tids[] = $parent->id();
          }
        }

        $uri = $exported_terms[$term->id()]['uri'];
        if ($settings['root_level'] == 'conceptscheme' || count($parent_tids) > 0) {
          // Add prefLabel and description.
          $ppt->addLiteral($project_id, $uri, 'preferredLabel', (!empty($term->getName()) ? $term->getName() : 'label not set'), $pp_lang);
          if (!empty($term->getDescription())) {
            $raw_description = str_replace(array(
              "\r",
              "\n"
            ), '', strip_tags($term->getDescription()));
            if (!empty($raw_description)) {
              $ppt->addLiteral($project_id, $uri, 'definition', $raw_description, $pp_lang);
            }
          }

          // Add all other labels and data.
          foreach ($fields as $field_id => $field_schema) {
            if (!isset($field_schema['push_key'])) {
              continue;
            }
            if (isset($term->{$field_id})) {
              $custom_field_values = $term->{$field_id}->getValue();
              if (isset($field_schema['cardinality']) && $field_schema['cardinality'] != 1) {
                foreach ($custom_field_values as $value) {
                  $value = trim(isset($value['value']) ? $value['value'] : (isset($value['uri']) ? $value['uri'] : ''));
                  if (!empty($value)) {
                    $ppt->addLiteral($project_id, $uri, $field_schema['push_key'], $value, $pp_lang);
                  }
                }
              }
              else {
                $char = isset($field_schema['merge_char']) ? $field_schema['merge_char'] : ',';
                $field_value = (isset($custom_field_values[0]['value']) ? $custom_field_values[0]['value'] : (isset($custom_field_values[0]['uri']) ? $custom_field_values[0]['uri'] : ''));
                if ($char == ' ') {
                  $values = [$field_value];
                }
                else {
                  $values = explode($char, $field_value);
                }
                foreach ($values as $value) {
                  $value = trim($value);
                  if (!empty($value)) {
                    $ppt->addLiteral($project_id, $uri, $field_schema['push_key'], $value, $pp_lang);
                  }
                }
              }
            }
          }
        }

        // Add hash data to the database.
        // The term is a concept.
        if ($settings['root_level'] == 'conceptscheme' || count($parent_tids) > 0) {
          $concept = $ppt->getConcept($project_id, $uri, $this->skosProperties(), $pp_lang);
        }
        // The term is a concept scheme.
        else {
          $concept = [];
          $schemes = $ppt->getConceptSchemes($project_id, $pp_lang);
          foreach ($schemes as $scheme) {
            if ($scheme['uri'] == $uri) {
              $concept = $scheme;
              break;
            }
          }
        }
        $concept['drupalLang'] = $drupal_lang;
        $concept['ppLang'] = $pp_lang;

        $uri_lang = $this->getUri($concept);
        $hash = $this->hash($concept);
        $this->addHashData($term, $pp_lang, $uri_lang, $hash, $info['start_time']);
      }
    }

    $context['results']['translation_processed']++;
  }

  /**
   * Creates a batch for updating all terms of a taxonomy.
   *
   * @param string $update_type
   *   The update type: sync, powertagging_taxonomy_update or import.
   * @param Vocabulary $vocabulary
   *   A Drupal taxonomy object.
   * @param string $root_uri
   *   The URI of a concept scheme or the ID of a project
   * @param array $languages
   *   An array of languages:
   *    key = Drupal language
   *    value = PoolParty language.
   * @param array $data_properties
   *   An array of SKOS properties to be fetched.
   * @param bool $preserve_concepts
   *   TRUE if old concepts should be converted into freeterms, FALSE if they
   *   should be deleted.
   * @param int $concepts_per_request
   *   Count of concepts per http request.
   *
   * @throws \Exception
   *   A regular exception in case of a operation-breaking error.
   */
  public function updateTaxonomyTerms($update_type, Vocabulary $vocabulary, $root_uri, $languages, $data_properties, $preserve_concepts, $concepts_per_request) {
    $start_time = time();

    // Configure the batch data.
    switch ($update_type) {
      case 'import':
        $title = t('Import terms into taxonomy %name ...', array('%name' => $vocabulary->label()));
        $init_message = t('Starting with the import of the taxonomy terms.');
        break;

      default:
        $title = t('Updating taxonomy %name ...', array('%name' => $vocabulary->label()));
        $init_message = t('Starting with the update of the taxonomy terms.');
        break;
    }
    $batch = array(
      'title' => $title,
      'operations' => array(),
      'init_message' => $init_message,
      'progress_message' => t('Processed @current out of @total.'),
      'finished' => array('\Drupal\pp_taxonomy_manager\PPTaxonomyManagerBatches', 'updateTermsFinished'),
    );

    // Get all the concepts from the PoolParty server per language.
    $data_properties[] = 'skos:broader';
    $data_properties[] = 'skos:definition';
    /** @var SemanticConnectorPPTApi $ppt */
    $ppt = $this->config->getConnection()->getApi('PPT');

    $settings = $this->config->getConfig();
    $top_term_uris = array();
    $concepts = array();
    $count = 0;
    if ($settings['root_level'] == 'project') {
      foreach ($languages as $drupal_lang => $pp_lang) {
        $concepts[$pp_lang] = array();
        $concept_schemes = $ppt->getConceptSchemes($root_uri, $pp_lang);
        if (is_null($concept_schemes)) {
          throw new \Exception('Error while fetching the concept schemes for project "' . $root_uri . '"');
        }
        foreach ($concept_schemes as $concept_scheme) {
          // Get the "hasTopConcept" relations of the scheme.
          $top_concepts = $ppt->getTopConcepts($root_uri, $concept_scheme['uri'], $data_properties, $pp_lang);
          $concept_scheme_children = array();
          if (is_null($top_concepts)) {
            throw new \Exception('Error while fetching the top concepts for URI "' . $concept_scheme['uri'] . '"');
          }
          elseif (is_array($top_concepts)) {
            foreach ($top_concepts as $top_concept) {
              $concept_scheme_children[] = $top_concept['uri'];
            }
          }

          // Add the concept scheme itself.
          $concept_scheme_array = array(
            'uri' => $concept_scheme['uri'],
            'prefLabel' => $concept_scheme['title'],
            'narrowers' => $concept_scheme_children,
            'drupalLang' => $drupal_lang,
            'ppLang' => $pp_lang,
          );
          $concepts[$pp_lang][$this->getUri($concept_scheme_array)] = $concept_scheme_array;

          $top_term_uris[] = $concept_scheme['uri'] . '@' . $pp_lang;
          // Add the top concepts and concepts.
          $tree = $ppt->getSubTree($root_uri, $concept_scheme['uri'], $data_properties, $pp_lang);
          if (is_null($tree)) {
            throw new \Exception('Error while fetching the subtree for URI "' . $concept_scheme['uri'] . '"');
          }
          $tree_list = $this->tree2list($tree, $drupal_lang, $pp_lang);

          // Add the fake broader relation to the concept scheme for all top
          // concepts.
          if (is_array($tree)) {
            foreach ($tree_list as &$concept) {
              if (in_array($concept['uri'], $concept_scheme_children)) {
                $concept['broaders'] = array($concept_scheme['uri']);
              }
              unset($top_concept);
            }
          }
          $concepts[$pp_lang] = array_merge($concepts[$pp_lang], $tree_list);
          $count += count($tree_list);
        }
      }
    }
    else {
      foreach ($languages as $drupal_lang => $pp_lang) {
        $concepts[$pp_lang] = array();
        $top_concepts = $ppt->getTopConcepts($this->config->getProjectId(), $root_uri, $data_properties, $pp_lang);
        if (is_null($top_concepts)) {
          throw new \Exception('Error while fetching the top concepts for URI "' . $root_uri . '"');
        }
        elseif (is_array($top_concepts)) {
          foreach ($top_concepts as $top_concept) {
            $top_term_uris[] = $top_concept['uri'] . '@' . $pp_lang;
          }
        }
        // Includes top concepts and concepts.
        $tree = $ppt->getSubTree($this->config->getProjectId(), $root_uri, $data_properties, $pp_lang);
        if (is_null($tree)) {
          throw new \Exception('Error while fetching the subtree for URI "' . $root_uri . '"');
        }
        $tree_list = $this->tree2list($tree, $drupal_lang, $pp_lang);
        $concepts[$pp_lang] = array_merge($concepts[$pp_lang], $tree_list);
        $count += count($tree_list);
      }
    }

    // Set additional data.
    $info = array(
      'total' => $count,
      'start_time' => $start_time,
      'top_concept_uris' => $top_term_uris,
      'vid' => $vocabulary->id(),
    );

    // Enable the translation for the taxonomy if required.
    $this->enableTranslation($vocabulary, $languages);

    // Set the update operations.
    foreach ($concepts as $pp_lang => $lang_concepts) {
      for ($i = 0; $i < count($lang_concepts); $i += $concepts_per_request) {
        $concept_list = array_slice($lang_concepts, $i, $concepts_per_request);
        $batch['operations'][] = array(
          array(
            '\Drupal\pp_taxonomy_manager\PPTaxonomyManagerBatches',
            'updateTerms'
          ),
          array(
            $this,
            $concept_list,
            $pp_lang,
            $vocabulary->id(),
            $vocabulary->id(),
            $info,
          ),
        );
      }
    }

    // Set the update parents operations.
    foreach ($concepts as $pp_lang => $lang_concepts) {
      for ($i = 0; $i < count($lang_concepts); $i += $concepts_per_request) {
        $concept_list = array_slice($lang_concepts, $i, $concepts_per_request);
        $batch['operations'][] = array(
          array(
            '\Drupal\pp_taxonomy_manager\PPTaxonomyManagerBatches',
            'updateTermParents'
          ),
          array($this, $concept_list, $info),
        );
      }
    }

    // Set the delete operations for the removed concepts.
    $batch['operations'][] = array(
      array('\Drupal\pp_taxonomy_manager\PPTaxonomyManagerBatches', 'deleteVocabulary'),
      array($this, $vocabulary->id(), $preserve_concepts),
    );

    // Set the log operation.
    $batch['operations'][] = array(
      array('\Drupal\pp_taxonomy_manager\PPTaxonomyManagerBatches', 'saveVocabularyLog'),
      array($this, $vocabulary->id(), $info),
    );

    // Start the batch.
    batch_set($batch);
  }

  /**
   * Batch process method for updating taxonomy terms from a PoolParty server.
   *
   * @param array $concepts
   *   The concepts that are to be updated.
   * @param string $pp_lang
   *   The PoolParty language of the concepts.
   * @param string $vid
   *   The taxonomy ID where the terms should be updated.
   * @param string $machine_name
   *   The taxonomy machine_name where the terms should be updated.
   * @param array $info
   *   An associative array of information about the batch process.
   * @param array $context
   *   The batch context to transmit data between different calls.
   */
  public function updateBatch($concepts, $pp_lang, $vid, $machine_name, $info, &$context) {
    $uris = array_keys($concepts);
    $processed_uris = array();
    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();

    // Get mapping data for every concept.
    $term_query = \Drupal::database()->select('pp_taxonomy_manager_terms', 't');
    $term_query->fields('t', array('tid', 'language', 'uri', 'hash'));
    $term_query->condition('t.tmid', $this->config->id());
    $term_query->condition('t.language', $pp_lang);
    $term_query->condition('t.vid', $vid);
    $term_query->condition('t.uri', $uris, 'IN');
    $result = $term_query->execute();

    // Update all existing terms.
    while ($record = $result->fetch()) {
      $concept = $concepts[$record->uri];
      $hash = $this->hash($concept);
      if ($record->hash != $hash) {
        $term = Term::load($record->tid);
        // Normal update.
        if ($concept['drupalLang'] == $default_language) {
          $term = $this->mapTaxonomyTermDetails($term, $concept);
        }
        // Translation update.
        else {
          // Get the translated version of the taxonomy term.
          $translation = $term->getTranslation($concept['drupalLang']);
          $mapped_translation = $this->mapTaxonomyTermDetails($translation, $concept);
          $term->addTranslation($concept['drupalLang'], $mapped_translation->toArray());
        }
        $term->save();
        $this->updateHashData($term, $pp_lang, $hash, $info['start_time']);
        $context['results']['updated_terms'][$record->uri] = $record->tid;
        \Drupal::logger('pp_taxonomy_manager')->notice('Taxonomy term updated: %name (TID = %tid) (%lang)', array(
          '%name' => $term->getName(),
          '%tid' => $term->id(),
          '%lang' => $concept['drupalLang'],
        ));
      }
      else {
        $context['results']['skipped_terms'][$record->uri] = $record->tid;
      }

      $processed_uris[] = $record->uri;
      $context['results']['processed']++;
    }

    // Create new terms for new existing concepts.
    $new_uris = array_diff($uris, $processed_uris);
    if (!empty($new_uris)) {
      foreach ($new_uris as $uri) {
        // Check if a term with the same URI and language already exists
        // in the taxonomy.
        $concept = $concepts[$uri];

        $query = \Drupal::entityQuery('taxonomy_term');
        $query->condition('vid', $machine_name);
        $query->condition('field_uri', $concept['uri']);

        $result = $query->execute();
        $tid = reset($result);

        $term_exists = FALSE;
        if ($tid) {
          $term = Term::load($tid);
          if ($term !== FALSE) {
            $term_exists = TRUE;
          }
        }
        // Check if a term with the same label (without a URI) already exists.
        else {
          $label_query = \Drupal::database()->select('taxonomy_term_field_data', 't');
          $label_query->fields('t', array('tid'));
          $label_query->condition('t.vid', $vid);
          $label_query->condition('t.name', $concept['prefLabel']);
          // Only the ones without a URI, even though the rest should already
          // be filtered out.
          $label_query->leftJoin('taxonomy_term__field_uri', 'u', 't.tid = u.entity_id');
          $label_query->isNull('u.field_uri_uri');

          $tid = $label_query->execute()
            ->fetchField();

          if ($tid) {
            $term = Term::load($tid);
            if ($term !== FALSE) {
              $term_exists = TRUE;
            }
          }
        }

        if (!$term_exists) {
          $term = Term::create(array('vid' => $vid));
        }

        // Normal update.
        if ($concept['drupalLang'] == $default_language) {
          $term = $this->mapTaxonomyTermDetails($term, $concept);
        }
        // Translation update.
        else {
          // Get the translated version of the taxonomy term.
          if ($term->hasTranslation($concept['drupalLang'])) {
            $translation = $term->getTranslation($concept['drupalLang']);
          }
          else {
            $translation = clone $term;
          }
          $mapped_translation = $this->mapTaxonomyTermDetails($translation, $concept);
          $term->addTranslation($concept['drupalLang'], $mapped_translation->toArray());
        }

        $term->save();

        // Add the hash to the hash table.
        $uri = $this->getUri($concept);
        $hash = $this->hash($concept);
        $this->addHashData($term, $pp_lang, $uri, $hash, $info['start_time']);
        if ($term_exists) {
          $context['results']['updated_terms'][$uri] = $term->id();
        }
        else {
          $context['results']['created_terms'][$uri] = $term->id();
        }
        $context['results']['processed']++;
        \Drupal::logger('pp_taxonomy_manager')->notice('Taxonomy term created: %name (TID = %tid)', array(
          '%name' => $term->getName(),
          '%tid' => $term->id(),
        ));
      }
    }
  }

  /**
   * Batch process method for updating the parents for the taxonomy terms.
   *
   * @param array $concepts
   *   The concepts that are to be updated.
   * @param array $info
   *   An associative array of information about the batch process.
   * @param array $context
   *   The batch context to transmit data between different calls.
   */
  public function updateParentsBatch($concepts, $info, &$context) {
    $changed_terms = $context['results']['updated_terms'] + $context['results']['created_terms'];
    $all_terms = $changed_terms + $context['results']['skipped_terms'];
    $parent_values = array();
    $handled_tids = array();
    foreach ($concepts as $concept) {
      $concept_uri = $this->getUri($concept);

      // Check if concept is updated or new.
      if (!isset($changed_terms[$concept_uri])) {
        $context['results']['processed_parents']++;
        continue;
      }

      // If the concept is a top concept then set to the top of the tree.
      $parents = array();
      if (in_array($concept_uri, $info['top_concept_uris'])) {
        $parents[] = 0;
      }

      if (isset($concept['broaders']) && !empty($concept['broaders'])) {
        foreach ($concept['broaders'] as $broader) {
          $broader_uri = $broader . '@' . $concept['ppLang'];
          if (isset($all_terms[$broader_uri])) {
            $parents[] = $all_terms[$broader_uri];
          }
        }
      }

      if (empty($parents)) {
        $parents = array(0);
      }
      foreach ($parents as $delta => $parent_tid) {
        $parent_values[] = array(
          'bundle' => $info['vid'],
          'deleted' => 0,
          'entity_id' => $all_terms[$concept_uri],
          'revision_id' => $all_terms[$concept_uri],
          'langcode' => $concept['drupalLang'],
          'delta' => $delta,
          'parent_target_id' => (int) $parent_tid,
        );
      }
      $handled_tids[] = $all_terms[$concept_uri];
      $context['results']['processed_parents']++;
    }

    if (!empty($parent_values)) {
      // Delete old hierarchy values.
      $delete_query = \Drupal::database()->delete('taxonomy_term__parent');
      $delete_query->condition('entity_id', $handled_tids, 'IN');
      $delete_query->execute();

      // Insert new hierarchy values.
      $query = \Drupal::database()->insert('taxonomy_term__parent')
        ->fields(array('bundle', 'deleted', 'entity_id', 'revision_id', 'langcode', 'delta', 'parent_target_id'));

      foreach ($parent_values as $parent_value) {
        $query->values($parent_value);
      }
      $query->execute();
    }
  }

  /**
   * Batch process method for deleting taxonomy terms.
   *
   * @param string $vid
   *   The taxonomy ID where the terms should be updated.
   * @param bool $preserve_concepts
   *   TRUE if old concepts should be converted into freeterms, FALSE if they
   * @param array $context
   *   The batch context to transmit data between different calls.
   */
  public function deleteBatch($vid, $preserve_concepts, &$context) {
    $all_terms = $context['results']['updated_terms'] + $context['results']['created_terms'] + $context['results']['skipped_terms'];
    $all_terms = array_values($all_terms);
    $database = \Drupal::database();

    // Delete all terms with a URI, which were not part of the sync/import process.
    $term_delete_query = $database->select('taxonomy_term_field_data', 't');
    $term_delete_query->fields('t', array('tid', 'name'));
    $term_delete_query->condition('t.vid', $vid);
    $term_delete_query->join('taxonomy_term__field_uri', 'u', 't.tid = u.entity_id');
    $term_delete_query->addField('u', 'field_uri_uri', 'uri');
    if (!empty($all_terms)) {
      $term_delete_query->condition('t.tid', $all_terms, 'NOT IN');
    }
    $delete_concepts = $term_delete_query->execute()
      ->fetchAllAssoc('uri', \PDO::FETCH_ASSOC);

    // Delete the old concepts.
    if (!$preserve_concepts) {
      foreach ($delete_concepts as $uri => $delete_concept) {
        $delete_term = Term::load($delete_concept['tid']);
        $delete_term->delete();
        $context['results']['deleted_terms'][$uri] = $delete_concept['tid'];
        \Drupal::logger('pp_taxonomy_manager')->notice('Taxonomy term deleted: %name (TID = %tid)', array(
          '%name' => $delete_concept['name'],
          '%tid' => $delete_concept['tid'],
        ));
      }

      // Check if a freeterm-term already exists.
      $freeterm_tid_query = $database->select('taxonomy_term_field_data', 't');
      $freeterm_tid_query->fields('t', array('tid'));
      $freeterm_tid_query->condition('vid', $vid);
      $freeterm_tid_query->condition('name', 'Free Terms');
      $freeterm_tid_query->leftJoin('taxonomy_term__field_uri', 'u', 't.tid = u.entity_id');
      $freeterm_tid_query->isNull('u.field_uri_uri');
      $freeterm_tid = $freeterm_tid_query->execute()
        ->fetchField();

      // Check if there are any terms without URI left, which are not marked as
      // freeterms.
      $no_uri_query = \Drupal::database()->select('taxonomy_term_field_data', 't');
      $no_uri_query->fields('t', array('tid', 'name'));
      $no_uri_query->condition('t.vid', $vid);
      $no_uri_query->condition('t.name', ['Concepts', 'Free Terms'], 'NOT IN');
      $no_uri_query->leftJoin('taxonomy_term__field_uri', 'u', 't.tid = u.entity_id');
      $no_uri_query->isNull('u.field_uri_uri');
      if ($freeterm_tid) {
        $no_uri_query->join('taxonomy_term__parent', 'p', 'p.entity_id = t.tid');
        $no_uri_query->condition('p.parent_target_id', $freeterm_tid, '<>');
      }
      $no_uri_terms = $no_uri_query->execute()
        ->fetchAllAssoc('tid', \PDO::FETCH_ASSOC);

      // Delete the terms.
      foreach ($no_uri_terms as $no_uri_term_tid => $no_uri_term) {
        $delete_term = Term::load($no_uri_term_tid);
        $delete_term->delete();
        $context['results']['deleted_terms']['__delete_freeterm__' . $no_uri_term_tid] = $no_uri_term_tid;
        \Drupal::logger('pp_taxonomy_manager')->notice('Taxonomy term deleted: %name (TID = %tid)', array(
          '%name' => $no_uri_term['name'],
          '%tid' => $no_uri_term['tid'],
        ));
      }
    }
    // Turn the old concepts into freeterms.
    else {
      $make_freeterm_tids = [];
      foreach ($delete_concepts as $uri => $delete_concept) {
        $make_freeterm_tids[] = $delete_concept['tid'];
      }

      if (!empty($make_freeterm_tids)) {
        // Remove the URIs.
        $database->delete('taxonomy_term__field_uri')
          ->condition('entity_id', $make_freeterm_tids, 'IN')
          ->execute();

        // Remove them from the hash table.
        $database->delete('pp_taxonomy_manager_terms')
          ->condition('tid', $make_freeterm_tids, 'IN')
          ->execute();
      }

      // Check for all terms without a URI.
      $update_parent_query = $database->select('taxonomy_term_field_data', 't');
      $update_parent_query->fields('t', array('tid'));
      $update_parent_query->condition('t.vid', $vid);
      $update_parent_query->condition('t.name', ['Concepts', 'Free Terms'], 'NOT IN');
      $update_parent_query->leftJoin('taxonomy_term__field_uri', 'u', 't.tid = u.entity_id');
      $update_parent_query->isNull('u.field_uri_uri');
      $update_parent_tids = $update_parent_query->execute()
        ->fetchCol();

      // Move them under a freeterms-term.
      if (!empty($update_parent_tids)) {
        // Check if a freeterm-term already exists.
        $freeterm_tid_query = $database->select('taxonomy_term_field_data', 't');
        $freeterm_tid_query->fields('t', array('tid'));
        $freeterm_tid_query->condition('vid', $vid);
        $freeterm_tid_query->condition('name', 'Free Terms');
        $freeterm_tid_query->leftJoin('taxonomy_term__field_uri', 'u', 't.tid = u.entity_id');
        $freeterm_tid_query->isNull('u.field_uri_uri');
        $freeterm_tid = $freeterm_tid_query->execute()
          ->fetchField();

        // Create the freeterm-term if it doesn't exist yet.
        if (!$freeterm_tid) {
          $term = Term::create([
            'name' => 'Free Terms',
            'vid' => $vid,
          ]);
          $term->save();
          $freeterm_tid = $term->id();
        }

        // Update the parents of the preserved tags.
        $database->update('taxonomy_term__parent')
          ->fields(array(
            'parent_target_id' => $freeterm_tid,
          ))
          ->condition('entity_id', $update_parent_tids, 'IN')
          ->execute();
      }
    }

    // Delete the "Concepts" term if it was created by the PowerTagging module.
    $static_delete_query = $database->select('taxonomy_term_field_data', 't');
    $static_delete_query->fields('t', array('tid'));
    $static_delete_query->condition('t.vid', $vid);
    $static_delete_query->condition('t.name', 'Concepts');
    $static_delete_query->leftJoin('taxonomy_term__field_uri', 'u', 't.tid = u.entity_id');
    $static_delete_query->isNull('u.field_uri_uri');
    $concepts_tid = $static_delete_query->execute()
      ->fetchField();
    if (!empty($concepts_tid)) {
      $delete_term = Term::load($concepts_tid);
      $delete_term->delete();
    }
  }

  /**
   * Deletes the term from the hash table.
   *
   * @param int $tid
   *   The ID of the taxonomy term.
   */
  public static function deleteTaxonomyTerm($tid) {
    $delete_query = \Drupal::database()->delete('pp_taxonomy_manager_terms');
    $delete_query->condition('tid', $tid);
    $delete_query->execute();
  }

  /**
   * Inserts the new statistic log.
   *
   * @param int $vid
   *   The Drupal taxonomy ID.
   * @param int $start_time
   *   The start time of the batch.
   * @param int $end_time
   *   The end time of the batch.
   */
  public function addLog($vid, $start_time, $end_time) {
    $insert_query = \Drupal::database()->insert('pp_taxonomy_manager_logs');
    $insert_query->fields(array(
      'tmid' => $this->config->id(),
      'vid' => $vid,
      'start_time' => $start_time,
      'end_time' => $end_time,
      'uid' => \Drupal::currentUser()->id(),
    ));
    $insert_query->execute();
  }

  /**
   * Deletes all synchronization data.
   *
   * @param int $vid
   *   The Drupal taxonomy ID.
   */
  public function deleteSyncData($vid) {
    // Delete the log data.
    $delete_query = \Drupal::database()->delete('pp_taxonomy_manager_logs');
    $delete_query->condition('tmid', $this->config->id());
    $delete_query->condition('vid', $vid);
    $delete_query->execute();

    // Delete the hash data.
    $delete_query = \Drupal::database()->delete('pp_taxonomy_manager_terms');
    $delete_query->condition('tmid', $this->config->id());
    $delete_query->condition('vid', $vid);
    $delete_query->execute();
  }

  /**
   * Calculates the remaining time of a batch process.
   *
   * @param int $start_time
   *   The start time.
   * @param int $processed
   *   The count of processed items.
   * @param int $total
   *   The total count of items.
   *
   * @return string
   *   The remaining time in a human readable string.
   */
  public function calculateRemainingTime($start_time, $processed, $total) {
    $time_string = '';
    if ($processed > 0) {
      $remaining_time = floor((time() - $start_time) / $processed * ($total - $processed));
      if ($remaining_time > 0) {
        $time_string = (floor($remaining_time / 3600) % 24) . ' hours ' . (floor($remaining_time / 60) % 60) . ' minutes ' . ($remaining_time % 60) . ' seconds';
      }
      else {
        $time_string = t('Done.');
      }
    }

    return $time_string;
  }

  /**
   * Maps a taxonomy term data with a PoolParty concept.
   *
   * @param Term $term
   *   The object of the taxonomy term, which will receive the new detail data.
   * @param object $concept
   *   A concept detail data to update the term with.
   *
   * @return Term
   *   The mapped taxonomy term.
   */
  protected function mapTaxonomyTermDetails($term, $concept) {
    $term->setName($concept['prefLabel']);
    $term->get('field_uri')->setValue($concept['uri']);
    $term->setDescription('');
    if (isset($concept['definitions'])) {
      $term->setDescription(implode(' ', $concept['definitions']));
    }

    $fields = self::taxonomyFields();
    foreach ($fields as $field_id => $field_schema) {
      if (!isset($field_schema['pull_key'])) {
        continue;
      }
      if (isset($field_schema['cardinality']) && $field_schema['cardinality'] != 1) {
        $values = [];
        if (!empty($concept[$field_schema['pull_key']])) {
          foreach ($concept[$field_schema['pull_key']] as $value) {
            // Remove multibyte-characters.
            $values[] = preg_replace('/[[:^print:]]/', "", $value);
          }
        }
        $term->get($field_id)->setValue($values);
      }
      else {
        $term->get($field_id)->setValue('');
        if (!empty($concept[$field_schema['pull_key']])) {
          $char = isset($field_schema['merge_char']) ? $field_schema['merge_char'] : ',';
          $value = implode($char, $concept[$field_schema['pull_key']]);
          // Remove multibyte-characters.
          $term->get($field_id)->setValue(preg_replace('/[[:^print:]]/', "", $value));
        }
      }
    }

    return $term;
  }

  /**
   * Creates a list of concepts from a tree.
   *
   * @param array $tree
   *   A list of concepts in tree format.
   * @param string $drupal_lang
   *   The Drupal language of the concepts.
   * @param string $pp_lang
   *   The PoolParty project language of the concepts.
   * @param int $depth
   *   The depth of the recursive function call.
   *
   * @return array
   *   A list of concept objects.
   */
  protected function tree2list(array $tree, $drupal_lang, $pp_lang, $depth = 0) {
    $concepts = array();
    foreach ($tree as $subtree) {
      if (is_array($subtree) && !empty($subtree['concept'])) {
        // If a concept is in the top level but is not a top concept, then
        // remove its broaders (it's important for other languages if is not
        // translated consistently).
        if ($depth == 0) {
          unset($subtree['concept']['broaders']);
        }
        $subtree['concept']['drupalLang'] = $drupal_lang;
        $subtree['concept']['ppLang'] = $pp_lang;
        $concept_uri = $this->getUri($subtree['concept']);
        $concepts[$concept_uri] = $subtree['concept'];
        if (!empty($subtree['narrowers'])) {
          $tree_list = $this->tree2list($subtree['narrowers'], $drupal_lang, $pp_lang, ($depth + 1));
          $concepts = array_merge($concepts, $tree_list);
        }
      }
    }
    return $concepts;
  }

  /**
   * Inserts the new created concept to the database.
   *
   * @param Term $term
   *   A Drupal taxonomy term.
   * @param string $pp_lang
   *   The PoolParty language used.
   * @param string $uri
   *   The URI with language prefix of a concept.
   * @param string $hash
   *   The new hash data.
   * @param int $start_time
   *   The start time of the batch.
   */
  protected function addHashData($term, $pp_lang, $uri, $hash, $start_time) {
    $insert_query = \Drupal::database()->insert('pp_taxonomy_manager_terms');
    $insert_query->fields(array(
      'tid' => $term->id(),
      'language' => $pp_lang,
      'vid' => $term->getVocabularyId(),
      'tmid' => $this->config->id(),
      'synced' => $start_time,
      'uri' => $uri,
      'hash' => $hash,
    ));
    $insert_query->execute();
  }

  /**
   * Updates the hash data for a taxonomy term.
   *
   * @param Term $term
   *   The taxonomy term.
   * @param string $pp_lang
   *   The PoolParty language used.
   * @param string $hash
   *   The new hash data.
   * @param int $start_time
   *   The synchonization start time.
   */
  protected function updateHashData($term, $pp_lang, $hash, $start_time) {
    $update_query = \Drupal::database()->update('pp_taxonomy_manager_terms');
    $update_query->fields(array(
      'synced' => $start_time,
      'hash' => $hash,
    ));
    $update_query->condition('vid', $term->getVocabularyId());
    $update_query->condition('tid', $term->id());
    $update_query->condition('language', $pp_lang);
    $update_query->execute();
  }

  /**
   * Creates a hash code from a concept $concept.
   *
   * @param array $concept
   *   An associative concept array from PoolParty.
   *
   * @return string
   *   The hash code.
   */
  protected function hash($concept) {
    return hash('md5', serialize($concept));
  }

  /**
   * Returns all SKOS properties of the taxonomy fields.
   *
   * @return array
   *   List of SKOS properties.
   */
  protected function skosProperties() {
    $fields = self::taxonomyFields();
    $properties = array();
    foreach ($fields as $field) {
      if (isset($field['property'])) {
        $properties[] = $field['property'];
      }
    }

    $properties[] = 'skos:broader';
    $properties[] = 'skos:definition';

    return $properties;
  }

  /**
   * Returns all SKOS properties of the taxonomy fields.
   *
   * @return array
   *   List of SKOS properties.
   */
  public static function getTaxonomyFieldProperties() {
    $fields = self::taxonomyFields();
    $properties = [];
    foreach ($fields as $field) {
      if (isset($field['property']) && !empty($field['property'])) {
        $properties[$field['property']] = $field['label'];
      }
    }

    return $properties;
  }

  /**
   * Returns the URI with language of a concept.
   *
   * @param array $concept
   *   The associative array of a concept.
   *
   * @return string
   *   The uri with the language (e.g., http://a.concept.uri/1234@en).
   */
  protected function getUri($concept) {
    return $concept['uri'] . '@' . $concept['ppLang'];
  }

  /**
   * Returns a list of all additional fields for a PoolParty taxonomy.
   *
   * @return array
   *   A list additional fields.
   */
  protected static function taxonomyFields() {
    $taxonomy_field_schema = [
      'field_uri' => [
        'field_name' => 'field_uri',
        'type' => 'link',
        'label' => t('URI'),
        'description' => t('URI of the concept.'),
        'cardinality' => 1,
        'field_settings' => [],
        'required' => FALSE,
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
        'property' => 'skos:altLabel',
        'pull_key' => 'altLabels',
        'push_key' => 'alternativeLabel',
        'merge_char' => ',',
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
        'property' => 'skos:hiddenLabel',
        'pull_key' => 'hiddenLabels',
        'push_key' => 'hiddenLabel',
        'merge_char' => ',',
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
        'property' => 'skos:scopeNote',
        'pull_key' => 'scopeNotes',
        'push_key' => 'scopeNote',
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
        'property' => 'skos:related',
        'pull_key' => 'relateds',
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
        'property' => 'skos:exactMatch',
        'pull_key' => 'exactMatch',
      ],
    ];

    // Add the possibility to add custom fields  via hook here.
    $custom_fields = array();
    \Drupal::moduleHandler()->alter('pp_taxonomy_manager_custom_attributes', $custom_fields);
    if (!empty($custom_fields)) {
      foreach ($custom_fields as $field_id => $custom_field) {
        // Check if a property is given and it is not one of the custom_fields
        if (isset($custom_field['property']) && !isset($taxonomy_field_schema[$field_id]) && isset($custom_field['type']) && $custom_field['type'] == 'text') {
          $taxonomy_field_schema[$field_id] = $custom_field;
        }
      }
    }

    return $taxonomy_field_schema;
  }

  /**
   * Creates a machine readable name from a human readable name.
   *
   * @param string $name
   *   The human readable name.
   *
   * @return string
   *   The machine readable name.
   */
  public static function createMachineName($name) {
    $name = strtolower($name);
    return substr(preg_replace(array('@[^a-z0-9_]+@', '@_+@'), '_', $name), 0, 32);
  }

  /**
   * Returns all selected languages with the default language first.
   *
   * @param array $all_languages
   *   An array of languages:
   *    key = Drupal language
   *    value = PoolParty language.
   *
   * @return array
   *   All maped languages with the default language first.
   */
  public static function orderLanguages($all_languages) {
    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $languages[$default_language] = $all_languages[$default_language];
    unset($all_languages[$default_language]);
    if (!empty($all_languages)) {
      foreach ($all_languages as $drupal_lang => $pp_lang) {
        if (!empty($pp_lang)) {
          $languages[$drupal_lang] = $pp_lang;
        }
      }
    }

    return $languages;
  }

  /**
   * Get a list of project IDs that are already used by PoolParty Taxonomy
   * Manager configurations.
   *
   * @param string $connection_url
   *   optional: a URL to filter the configurations with.
   * @param array $ignored_config_ids
   *   optional: An array of IDs of PoolParty Taxonomy Manager configurations
   *   to ignore in the checks.
   *
   * @return array
   *   An associative array of used project IDs (project ID => config title).
   */
  public static function getUsedProjects($connection_url = NULL, $ignored_config_ids = array()) {
    $project_ids = array();
    $existing_configs = PPTaxonomyManagerConfig::loadMultiple();
    /** @var PPTaxonomyManagerConfig $existing_config */
    foreach ($existing_configs as $existing_config) {
      if (!in_array($existing_config->id(), $ignored_config_ids) && (is_null($connection_url) || $connection_url == $existing_config->getConnection()->getUrl())) {
        $existing_config_settings = $existing_config->getConfig();
        if ($existing_config_settings['root_level'] == 'conceptscheme') {
          $project_ids[$existing_config->getProjectId()] = $existing_config->getTitle();
        }
        else {
          foreach (array_values($existing_config_settings['taxonomies']) as $project_id) {
            $project_ids[$project_id] = $existing_config->getTitle();
          }
        }
      }
    }

    return $project_ids;
  }

  /**
   * Check if any of the synced Drupal taxonomies was changed in PoolParty.
   *
   * @param PPTaxonomyManagerConfig $pp_taxonomy_manager_config
   *   Optional; A specific Taxonomy Manager configuration to check for PoolParty
   *   updates. If none is given, all Taxonomy Manager configurations get checked.
   * @param string $project_id
   *   Optional; The ID of the PoolParty project to check. If none is given all
   *   relevant PP projects are checked.
   * @param int $vocabulary_id
   *   Optional; The ID of the Drupal taxonomy to use for the check. If none is
   *   given the last log for the first vocabulary found is used for the check.
   *
   * @return string[]
   *   Array of notification strings.
   */
  public static function checkPPChanges($pp_taxonomy_manager_config = NULL, $project_id = NULL, $vocabulary_id = NULL) {
    $notifications = array();

    if (!is_null($pp_taxonomy_manager_config)) {
      $configs = array($pp_taxonomy_manager_config);
    }
    else {
      $configs = PPTaxonomyManagerConfig::loadMultiple();
    }

    /** @var PPTaxonomyManagerConfig $config */
    foreach ($configs as $config) {
      $settings = $config->getConfig();

      $project_ids_to_check = array();
      /** @var SemanticConnectorPPTApi $ppt_api */
      $ppt_api = $config->getConnection()
        ->getApi('PPT');

      $projects = $ppt_api->getProjects();
      foreach ($projects as $project) {
        if ($settings['root_level'] == 'project') {
          foreach ($settings['taxonomies'] as $vid => $taxonomy_project_id) {
            if ($project['id'] == $taxonomy_project_id && (is_null($project_id) || $project_id == $project['id']) && (is_null($vocabulary_id) || $vocabulary_id == $vid)) {
              $project_ids_to_check[$vid] = $project['id'];
            }
          }
        }
        else {
          if ($project['id'] == $config->getProjectId() && (is_null($project_id) || $project_id == $project['id'])) {
            foreach ($settings['taxonomies'] as $vid => $taxonomy_scheme_id) {
              if (is_null($vocabulary_id) || $vocabulary_id == $vid) {
                $project_ids_to_check[$vid] = $project['id'];
              }
            }
          }
        }
      }
      $project_ids_to_check = array_unique($project_ids_to_check);

      if (!empty($project_ids_to_check)) {
        foreach ($project_ids_to_check as $vid => $project_id_to_check) {
          $vocabulary = Vocabulary::load($vid);

          if ($vocabulary !== FALSE) {
            $taxonomy_title = $vocabulary->label();
            $last_log = $config->getLastLog($vid);

            $history = $config->getConnection()
              ->getApi('PPT')
              ->getHistory($project_id_to_check, $last_log['end_time'], NULL, array(
                'resourceChangeAddition',
                'resourceChangeRemoval',
                'resourceChangeUpdate',
                'addRelation',
                'removeRelation',
                'addLiteral',
                'removeLiteral',
                'updateLiteral'
              ));

            if (!empty($history)) {
              // Get the project title.
              $project_title = '';
              foreach ($projects as $project) {
                if ($project['id'] == $project_id_to_check) {
                  $project_title = $project['title'];
                }
              }

              $notifications[] = t('PoolParty project "%ppproject" was updated and Drupal vocabulary "%vocname" needs to be synced.', array('%ppproject' => $project_title, '%vocname' => $taxonomy_title)) . (\Drupal::currentUser()->hasPermission('administer pp_taxonomy_manager') ? ' ' . Link::fromTextAndUrl('sync now', Url::fromRoute('entity.pp_taxonomy_manager.sync', array('config' => $config->id(), 'taxonomy' => $vid)))->toString() : '');
            }
          }
        }
      }
    }

    return $notifications;
  }

  /**
   * This method extends an existing form with the data properties.
   *
   * @param array $form
   *   The form array.
   * @param array $default_values
   *   The default values.
   */
  public static function addDataPropertySelection(&$form, $default_values) {
    $properties = PPTaxonomyManager::getTaxonomyFieldProperties();
    $form['data_properties'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Select the properties that will be saved in addition to the taxonomy terms'),
      '#description' => t('The data for the unselected properties will be deleted from the Drupal taxonomy.'),
      '#options' => $properties,
      '#default_value' => $default_values,
    );
  }
}