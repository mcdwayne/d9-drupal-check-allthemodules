<?php

namespace Drupal\textrazor;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\Promise;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MetatagManager.
 *
 * @package Drupal\metatag
 */
class TextrazorManager implements TextrazorManagerInterface {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Bundles with TextRazor activated.
   *
   * @var array
   */
  protected $activeBundles = [];

  /**
   * Data types from TextRazor response.
   *
   * @var array
   */
  protected $dataTypes = ['coarseTopics', 'topics', 'categories', 'entities'];

  /**
   * Configuration values for fields to be appended.
   *
   * @var array
   */
  protected $fieldDefinition = [
    'tags' => [
      'field_name' => 'field_tags',
      'label' => 'Tags',
      'widget' => 'entity_reference_autocomplete_tags',
      'type' => 'term',
    ],
    'categories' => [
      'field_name' => 'field_categories',
      'label' => 'Categories',
      'widget' => 'entity_reference_autocomplete_tags',
      'type' => 'term',
    ],
    'dates' => [
      'field_name' => 'field_dates',
      'label' => 'Dates',
      'widget' => 'entity_reference_autocomplete_tags',
      'type' => 'term',
    ],
    'entities' => [
      'field_name' => 'field_entities',
      'label' => 'Entities',
      'widget' => 'entity_reference_autocomplete_tags',
      'type' => 'term',
    ],
    'industries' => [
      'field_name' => 'field_industries',
      'label' => 'Industries',
      'widget' => 'entity_reference_autocomplete_tags',
      'type' => 'term',
    ],
    'newstopics' => [
      'field_name' => 'field_newstopics',
      'label' => 'News topics',
      'widget' => 'entity_reference_autocomplete_tags',
      'type' => 'term',
    ],
    'organizations' => [
      'field_name' => 'field_organizations',
      'label' => 'Organizations',
      'widget' => 'entity_reference_autocomplete_tags',
      'type' => 'term',
    ],
    'other_entities' => [
      'field_name' => 'field_other_entities',
      'label' => 'Uncategorized entities',
      'widget' => 'entity_reference_autocomplete_tags',
      'type' => 'term',
    ],
    'people' => [
      'field_name' => 'field_people',
      'label' => 'People',
      'widget' => 'entity_reference_autocomplete_tags',
      'type' => 'term',
    ],
    'places' => [
      'field_name' => 'field_places',
      'label' => 'Places',
      'widget' => 'entity_reference_autocomplete_tags',
      'type' => 'term',
    ],
    'topics' => [
      'field_name' => 'field_topics',
      'label' => 'Topics',
      'widget' => 'entity_reference_autocomplete_tags',
      'type' => 'term',
    ],
    'textrazor_response' => [
      'field_name' => 'field_textrazor_response',
      'label' => 'TextRazor response JSON',
      'widget' => 'text_textarea',
      // 'type' is for internal use only, not API related.
      'type' => 'textarea',
    ],
  ];

  /**
   * Base array to create field storage for term fields.
   *
   * @var array
   */
  protected $termFieldStorageConfig = [
    // field_name has to be set on each instance.
    'field_name' => '',
    'entity_type' => 'node',
    'type' => 'entity_reference',
    'cardinality' => -1,
    'settings' => [
      'target_type' => 'taxonomy_term',
    ],
  ];

  /**
   * Base array to create field instances for term fields.
   *
   * @var array
   */
  protected $termFieldConfig = [
    // field_name, bundle, label and vocabulary has to be set on each instance.
    'field_name' => '',
    'entity_type' => 'node',
    'bundle' => '',
    'label' => '',
    'settings' => [
      'handler_settings' => [
        'target_bundles' => [],
        'sort' => [
          'field' => '_none',
        ],
        'auto_create' => TRUE,
        'auto_create_bundle' => '',
      ],
    ],
  ];

  /**
   * Base array to create field storage for text fields.
   *
   * @var array
   */
  protected $textFieldStorageConfig = [
    // field_name has to be set on each instance.
    'field_name' => '',
    'entity_type' => 'node',
    'type' => 'string_long',
    'cardinality' => 1,
  ];

  /**
   * Base array to create field instances for text fields.
   *
   * @var array
   */
  protected $textFieldConfig = [
    // field_name, bundle and label has to be set on each instance.
    'field_name' => '',
    'entity_type' => 'node',
    'bundle' => '',
    'label' => '',
  ];

  /**
   * Promises array for async translations requests.
   *
   * @var array
   */
  private $promises = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, EntityManagerInterface $entityManager) {
    $this->activeBundles = array_keys(\Drupal::config('textrazor.settings')->get('active_bundles'));
    $this->entityFieldManager = $entityFieldManager;
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataTypes() {
    return array_keys($this->fieldDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTags() {
    $term_storage = $this->entityManager->getStorage('taxonomy_term');
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('status', 1);
    $query->condition('vid', 'tags');
    $entity_ids = $query->execute();
    $entities = $term_storage->loadMultiple($entity_ids);
    return array_values(array_map(function ($term) {
      return $term->label();
    }, $entities));
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveBundles() {
    return $this->activeBundles;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTextrazorEnabled($form_id = '') {
    foreach ($this->activeBundles as $bundle_id) {
      $ids = ['node_' . $bundle_id . '_form', 'node_' . $bundle_id . '_edit_form'];
      if (in_array($form_id, $ids)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareForm(array &$form) {

    // TextRazor fields wrapper for Seven's sidebar.
    $form['textrazor'] = [
      '#type' => 'details',
      '#title' => 'Content categorization',
      '#open' => TRUE,
      '#group' => 'advanced',
      'suggest_button' => [
        '#access' => TRUE,
        '#type' => 'button',
        '#value' => 'Suggest',
        '#attributes' => [
          'data-drupal-action' => 'textrazor-suggest',
        ],
      ],
      'field_suggested_tags_target_id' => [
        '#access' => TRUE,
        '#validated' => TRUE,
        '#disabled' => TRUE,
        '#type' => 'selectize',
        '#title' => 'suggested tags',
        '#description' => 'Click suggested tags to add them.',
        '#multiple' => 1,
        '#weight' => 20,
        '#settings' => [
          'create' => FALSE,
          'sortField' => 'label',
          'allowEmptyOption' => FALSE,
          'highlight' => TRUE,
          'persist' => TRUE,
        ],
        '#options' => $this->getMergedOptionList($form),
        '#default_value' => $this->getMergedOptionList($form),
      ],
    ];

    // The storage of the JSON shouldn't be visible for the user.
    $form['field_textrazor_response']['widget'][0]['value']['#type'] = 'hidden';

    // Move fields inside the new wrapper.
    foreach ($this->getDataTypes() as $dataType) {
      $fieldName = 'field_' . $dataType;
      $form[$fieldName]['widget']['target_id']['#maxlength'] = 9999999;
      $form['textrazor'][$fieldName] = $form[$fieldName];
      unset($form[$fieldName]);
    }

    $form['#attached']['library'][] = 'textrazor/textrazor.lib';
    $form['#attached']['library'][] = 'textrazor/textrazor.fields';
    $form['#attached']['drupalSettings']['textrazor']['dataTypes'] = $this->getDataTypes();
    $form['#attached']['drupalSettings']['textrazor']['currentTags'] = $this->getCurrentTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getMergedOptionList(array $form): array {
    $options = [];
    // These fields are not placed in the 'other suggested terms' field.
    $excluded_fields = [
      'categories',
      'newstopics',
      'other_entities',
      'topics',
      'textrazor_response',
    ];
    foreach ($this->getDataTypes() as $data_type) {
      $field_name = 'field_' . $data_type;
      if (!isset($form[$field_name]) || \in_array($data_type, $excluded_fields, TRUE) || !isset($form[$field_name]['widget']['target_id'])) {
        continue;
      }
      foreach ($form[$field_name]['widget']['target_id']['#default_value'] as $term) {
        $value = $term->getName();
        $options[$value] = $value;
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function removeTextrazorFields($bundle_id) {
    if (!empty($bundle_id)) {
      $currentBundleFields = $this->entityFieldManager->getFieldDefinitions('node', $bundle_id);
      foreach ($this->fieldDefinition as $vocabulary => $field) {
        if (isset($currentBundleFields[$field['field_name']])) {
          \Drupal\field\Entity\FieldConfig::loadByName('node', $bundle_id, $field['field_name'])->delete();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function appendTextrazorFields($bundle_id) {
    if (!empty($bundle_id)) {
      $currentBundleFields = $this->entityFieldManager->getFieldDefinitions('node', $bundle_id);
      foreach ($this->fieldDefinition as $vocabulary => $field) {
        if (!isset($currentBundleFields[$field['field_name']])) {
          // Chose the base configuration for the field and storage.
          if ($field['type'] === 'term') {
            $storageConfig = $this->termFieldStorageConfig;
            $fieldConfig = $this->termFieldConfig;
            $fieldConfig['settings']['handler_settings']['target_bundles'] = [
              $vocabulary => $vocabulary,
            ];
          }
          else {
            $storageConfig = $this->textFieldStorageConfig;
            $fieldConfig = $this->textFieldConfig;
          }

          // Create the storage entity only if it not exists.
          if (\Drupal\field\Entity\FieldStorageConfig::loadByName('node', $field['field_name']) === NULL) {
            $storageConfig['field_name'] = $field['field_name'];
            \Drupal\field\Entity\FieldStorageConfig::create($storageConfig)->save();
          }

          // Create the field instance in the bundle.
          $fieldConfig['field_name'] = $field['field_name'];
          $fieldConfig['bundle'] = $bundle_id;
          $fieldConfig['label'] = $field['label'];
          \Drupal\field\Entity\FieldConfig::create($fieldConfig)->save();

          // Setup the display mode in the create/edit form.
          entity_get_form_display('node', $bundle_id, 'default')
            ->setComponent(
              $field['field_name'], ['type' => $field['widget'], 'weight' => 20]
            )->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function classifyNode(EntityInterface $node) {
    if (!in_array($node->getType(), $this->activeBundles)) {
      return $node;
    }
    // Not applying when already classified in the frontend.
    if ($node->hasField('field_tags') && !empty($node->field_tags->getValue())) {
      return $node;
    }

    $text = $this->getTextToClassify($node);
    if (empty($text)) {
      return $node;
    }

    try {
      $response = $this->getTextrazorResponse($text)['response'];
    }
    catch (\Exception $e) {
      \Drupal::logger('textrazor')->error($e->getMessage());
      return $node;
    }

    if (!is_array($response)) {
      \Drupal::logger('textrazor')->error('Malformed response from TextRazor');
      return $node;
    }
    $fielded_terms = $this->prepareTerms($response);
    $field_definitions = $node->getFieldDefinitions();

    foreach ($fielded_terms as $field_id => $terms) {
      if (!array_key_exists($field_id, $field_definitions)) {
        continue;
      }
      // Get vocabulary ID for each taxonomy field.
      // @todo handle fields with multiple 'target_bundles'.
      $vid = reset($field_definitions[$field_id]->getSetting('handler_settings')['target_bundles']);
      $term_ids = [];
      foreach ($terms as $term) {
        // Load term and if not exists create it.
        $term_entity = $this->getTermByName($vid, $term);
        if (empty($term_entity) && !empty($term) && mb_detect_encoding($term, 'UTF-8')) {
          $term_entity = Term::create([
            'parent' => [],
            'name' => $term,
            'vid' => $vid,
          ]);
          $term_entity->save();
        }
        if (!empty($term_entity)) {
          $term_ids[] = $term_entity->id();
        }
      }

      $node->set($field_id, $term_ids);
    }

    return $node;
  }

  /**
   * Return the first term found in a vocabulary based on a name.
   */
  protected function getTermByName($vocabulary_id, $name) {
    $term_storage = $this->entityManager->getStorage('taxonomy_term');
    $term_entities = $term_storage->loadByProperties(['vid' => $vocabulary_id, 'name' => $name]);
    $term_entity = reset($term_entities);
    return $term_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTextToClassify(EntityInterface $node) {
    // @todo make fields to use configurable.
    $text = '';
    if ($node->hasField('field_teaser_text')) {
      $text .= $node->get('field_teaser_text')->value . "\n";
    }
    if ($node->hasField('field_paragraphs')) {
      $paragraphs = $node->field_paragraphs->getValue();
      foreach ($paragraphs as $field_value) {
        $paragraph = $this->entityManager->getStorage('paragraph')->load($field_value['target_id']);
        if (empty($paragraph)) {
          continue;
        }
        $fields = [
          'headline',
          'citation',
          'text',
          'statement_text',
          'statement_person',
        ];
        foreach ($fields as $field) {
          if ($paragraph->hasField('field_' . $field)) {
            $text .= $paragraph->get('field_' . $field)->value . "\n";
          }
        }
      }
    }
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function getTextrazorResponse(string $text) {
    $apiKey = \Drupal::config('textrazor.settings')->get('textrazor_apikey');
    \TextRazorSettings::setApiKey($apiKey);
    $textrazor = new \TextRazor();

    // Configure the query.
    // @see https://www.textrazor.com/docs/php#analysis
    $extractors = [
      'entities',
      'topics',
      'words',
      'phrases',
      'dependency-trees',
      'relations',
      'entailments',
      'senses',
      'spelling',
    ];
    foreach ($extractors as $extractor) {
      $textrazor->addExtractor($extractor);
    }
    $textrazor->addClassifier('textrazor_mediatopics');

    return $textrazor->analyze($text);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatedLabels(array $response, string $langcode = 'en') {
    $translated_terms = [];
    $cache = \Drupal::cache('textrazor');

    foreach ($this->dataTypes as $type) {
      if (array_key_exists($type, $response)) {
        foreach ($response[$type] as $term) {
          if ($this->filterEntitiesTerms($term)) {
            continue;
          }
          $uid = $this->getTermHash($term);

          // Checks if is already in cache.
          $cached_translation = $cache->get($uid);
          if (!empty($cached_translation)) {
            $translated_terms[$uid] = $cached_translation->data;
            // Only trigger requests if not present in cache.
            continue;
          }

          // Gets translation from the term information.
          $direct_translation = $this->getDirectTranslation($term, $langcode);
          if (!empty($direct_translation) && mb_detect_encoding($direct_translation, 'UTF-8')) {
            $translated_terms[$uid] = $direct_translation;
            $cache->set($uid, $direct_translation);
            // Only trigger requests if not present in cache.
            continue;
          }

          $term['uid'] = $uid;
          $this->requestAsyncTranslationFromWikipedia($term, $langcode);
        }
      }
    }

    $results = Promise\settle($this->promises)->wait();
    foreach ($results as $uid => $res) {
      if (isset($res['value'])) {
        $translation = $this->extractLabelFromWikipediaResponse($res['value']);
        if (!empty($translation) && mb_detect_encoding($translation, 'UTF-8')) {
          $translated_terms[$uid] = $translation;
          $cache->set($uid, $translation);
        }
      }
    }

    // Cleanup promises array to reuse it.
    $this->promises = [];

    foreach ($this->dataTypes as $type) {
      if (array_key_exists($type, $response)) {
        foreach ($response[$type] as $term) {
          if ($this->filterEntitiesTerms($term)) {
            continue;
          }
          $uid = $this->getTermHash($term);
          if (!isset($translated_terms[$uid])) {
            $term['uid'] = $uid;
            $this->requestAsyncTranslationFromWikidata($term);
          }
        }
      }
    }

    $results = Promise\settle($this->promises)->wait();
    foreach ($results as $uid => $res) {
      if (isset($res['value'])) {
        $translation = $this->extractLabelFromWikidataResponse($res['value'], $langcode);
        if (!empty($translation) && mb_detect_encoding($translation, 'UTF-8')) {
          $translated_terms[$uid] = $translation;
          $cache->set($uid, $translation);
        }
      }
    }

    // Cache that there is no translation at this moment.
    foreach ($this->dataTypes as $type) {
      if (array_key_exists($type, $response)) {
        foreach ($response[$type] as $term) {
          if ($this->filterEntitiesTerms($term)) {
            continue;
          }
          $uid = $this->getTermHash($term);
          if (!isset($translated_terms[$uid])) {
            $translated_terms[$uid] = '';
            // Set as no available translation, check again one week later.
            $cache->set($uid, '', time() + 60 * 60 * 24 * 7);
          }
        }
      }
    }

    return $translated_terms;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareTerms($response) {
    $terms = [];
    $response = $this->getAllNewstopics($response);
    $translated_labels = $this->getTranslatedLabels($response, 'de');
    foreach ($this->dataTypes as $type) {
      if (array_key_exists($type, $response)) {
        foreach ($response[$type] as $term) {
          if ($field = $this->getTermField($type, $term)) {
            if (!isset($terms[$field])) {
              $terms[$field] = [];
            }
            $uid = $this->getTermHash($term);
            $translated = isset($translated_labels[$uid]) ? $translated_labels[$uid] : '';
            $fallback_label = isset($term['label']) ? $term['label'] : '';
            if (!empty($translated) && !in_array($translated, $terms[$field])) {
              $terms[$field][] = $translated;
            }
            elseif (!empty($fallback_label) && mb_detect_encoding($fallback_label, 'UTF-8')) {
              $terms[$field][] = $fallback_label;
            }
          }
        }
      }
    }

    $terms['field_tags'] = $this->getFieldTagsTerms($response, $translated_labels);
    return $terms;
  }

  /**
   * Generates a unique hash for a term based on textrazor data.
   *
   * This hash can be used for caching keys for the same term on different
   * contexts as long use only term keys context-agnostic.
   *
   * @param array $term
   *   The part of the response with only the term.
   *
   * @return string
   *   The hash as a unique identifier of the term.
   */
  protected function getTermHash(array $term) {
    $hash = '';
    $hash .= isset($term['wikiLink']) ? $term['wikiLink'] : '';
    $hash .= isset($term['wikidataId']) ? $term['wikidataId'] : '';
    $hash .= isset($term['label']) ? $term['label'] : '';
    $hash .= isset($term['categoryId']) ? $term['categoryId'] : '';
    $hash .= isset($term['entityId']) ? $term['entityId'] : '';
    // Hash number 0.
    if (isset($term['entityID']) && $term['entityId'] === '0' && $term['unit'] === 'Number') {
      $hash .= 'entityId:0';
    }

    return md5($hash);
  }

  /**
   * Gets translation based on data from textrazor.
   *
   * Some terms have keys where the translated label can be extracted from.
   *
   * @param array $term
   *   The part of textrazor response with only the term.
   * @param string $langcode
   *   The language we are looking for translation.
   *
   * @return string|null
   *   If found a translated label, the label.
   */
  protected function getDirectTranslation(array $term, $langcode) {
    if (isset($term['wikiLink'])) {
      // The term can already have a reference to the translation.
      if (strpos($term['wikiLink'], 'http://' . $langcode . '.wikipedia.org/wiki/') !== FALSE) {
        // Looks for the last element of the URL, including accents and '-_()'.
        preg_match('/([0-9A-Za-zÀ-ÿ\-_\(\)]*)$/', $term['wikiLink'], $matches);
        // Re-format the translation from URL to readable words.
        $label = str_replace(['_', '(', ')'], [' ', '-', '-'], $matches[0]);
        return $label;
      }
    }
  }

  /**
   * Get translation of a term from Wikipedia.
   *
   * Fetch the wikipedia entity and select the label in the given language.
   *
   * @param array $term
   *   The term with all data provided by Textrazor.
   * @param string $languageCode
   *   The language code to extract the translation for.
   */
  protected function requestAsyncTranslationFromWikipedia(array $term, $languageCode) {
    // Skip if there is no reference to any Wikipedia entity.
    if (!isset($term['wikiLink'])) {
      return;
    }

    preg_match('/([0-9A-Za-z]*)$/', $term['wikiLink'], $matches);
    $wiki_key = $matches[0];
    $url = 'https://en.wikipedia.org/w/api.php?action=query&prop=langlinks&lllang=' . $languageCode . '&format=json&titles=' . $wiki_key;
    $request = \Drupal::httpClient();
    $this->promises[$term['uid']] = $request->requestAsync('GET', $url);

  }

  /**
   * Extracts the translated label from the Wikipedia response object.
   *
   * @param Psr\Http\Message\ResponseInterface $response_object
   *   The object coming from wikipedia API.
   *
   * @return string|null
   *   The translated label or NULL if no translation available.
   */
  protected function extractLabelFromWikipediaResponse($response_object) {
    if (empty($response_object)) {
      return;
    }
    $response = json_decode($response_object->getBody());
    // No always exist translation and the structure change.
    try {
      if (property_exists($response, 'query')) {
        $query_pages = get_object_vars($response->query->pages);
        $page = array_shift($query_pages);
        if (property_exists($page, 'langlinks')) {
          $lang_links = $page->langlinks;
          if (is_array($lang_links)) {
            return array_shift($lang_links)->{'*'};
          }
        }
      }
      return;
    }
    catch (\Exception $e) {
      error_log('Error: not available translation in wikipedia for ' . $wiki_key);
    }
  }

  /**
   * Get translation of a term from Wikidata.
   *
   * Fetch the wikidata entity and select the label in the given language.
   *
   * @param array $term
   *   The term with all data provided by Textrazor.
   */
  protected function requestAsyncTranslationFromWikidata(array $term) {
    // Skip if there is no reference to any Wikidata entity.
    if (!isset($term['wikidataId'])) {
      return;
    }

    $wiki_data_id = $term['wikidataId'];
    $url = "https://www.wikidata.org/wiki/Special:EntityData/" . $wiki_data_id . ".json";
    $request = \Drupal::httpClient();
    $this->promises[$term['uid']] = $request->requestAsync('GET', $url);
  }

  /**
   * Extracts the translated label from the Wikidata response object.
   *
   * @param Psr\Http\Message\ResponseInterface $response_object
   *   The object coming from wikidata API.
   *
   * @return string|null
   *   The translated label or NULL if no translation available.
   */
  protected function extractLabelFromWikidataResponse($response_object, $langcode = '') {
    if (empty($response_object)) {
      return;
    }
    $response = json_decode($response_object->getBody());
    try {
      if (isset($response->entities) && isset($response->entitites->{$wiki_data_id})) {
        $labels = $response->entities->{$wiki_data_id}->labels;
        if (property_exists($labels, $langcode)) {
          return $labels->{$langcode}->value;
        }
      }
      return;
    }
    catch (\Exception $e) {
      error_log('Error: not available translation in wikidata for ' . $wiki_key);
    }
  }

  /**
   * Get the field name for a given term.
   *
   * Performs a basic mapping for most of types and a filter and lookup
   * to the type for 'entities'.
   *
   * @param string $type
   *   The data type name of Textrazor.
   * @param array $term
   *   The term with all data provided by Textrazor.
   *
   * @return string|bool
   *   The field name to store the term, or FALSE is have to be dropped.
   */
  protected function getTermField($type, array $term) {
    $type_mapping = [
      'coarseTopics' => 'categories',
      'topics' => 'topics',
      'categories' => 'newstopics',
    ];
    $entity_mapping = [
      'Agent' => 'organizations',
      'Place' => 'places',
      'PopulatedPlace' => 'places',
      'Country' => 'places',
      'Settlement' => 'places',
      'Person' => 'people',
      'Date' => 'dates',
      'URL' => 'other-entities',
      'Other' => 'other-entities',
      'Industry' => 'industries',
    ];

    if (isset($type_mapping[$type])) {
      return 'field_' . $type_mapping[$type];
    }

    if ($type === 'entities') {
      // Discard meaningless entities.
      if (!$this->filterEntitiesTerms($term)) {
        $term_type = reset($term['type']);
        $field = array_key_exists($term_type, $entity_mapping) ? $entity_mapping[$term_type] : 'entities';
        return 'field_' . $field;
      }
    }
    return FALSE;
  }

  /**
   * Check if a term should be used or not.
   *
   * Discards the unclassified terms in 'entities' and numbers.
   *
   * @param array $term
   *   The term with all data provided by Textrazor.
   *
   * @return bool
   *   True if the term should be removed.
   */
  protected function filterEntitiesTerms($term) {
    return !isset($term['type']) || isset($term['unit']) && $term['unit'] === 'Number';
  }

  /**
   * Create term arrays for all elements of the Newstopics hierarchy.
   *
   * 'categories' type of Textrazor response stores Newstopics with the
   * hierarchy in the label with '>' separator. Converts it to terms array.
   *
   * @param array $response
   *   The Textrazor response.
   *
   * @return array
   *   All terms of the Newstopics categorization.
   */
  protected function getAllNewstopics($response) {
    if (!array_key_exists('categories', $response)) {
      return $response;
    }
    // @todo create newstopics hierarchy
    $news_topics = $response['categories'];
    $all_news_topics = [];
    foreach ($news_topics as $topic) {
      $splited = explode('>', $topic['label']);
      foreach ($splited as $split) {
        $new_term = $topic;
        $new_term['label'] = $split;
        $all_news_topics[$split] = $new_term;
      }
    }
    $response['categories'] = array_values($all_news_topics);

    return $response;
  }

  /**
   * Select terms for field_tags.
   *
   * The field_tags store the top 5 entities based on relevance.
   *
   * @param array $response
   *   The Textrazor response.
   * @param array $translated_labels
   *   Translations for the labels.
   *
   * @return array
   *   Translated labels of top 5 relevant terms.
   */
  protected function getFieldTagsTerms(array $response, array $translated_labels) {
    $tags = [];
    $filtered_entities = [];
    $count = 0;

    foreach ($response['entities'] as $term) {
      if (!$this->filterEntitiesTerms($term)) {
        $filtered_entities[] = $term;
      }
    }

    // Sorts 'entities' by relevanceScore.
    usort($filtered_entities, function ($a, $b) {
      return $a['relevanceScore'] < $b['relevanceScore'];
    });
    foreach ($filtered_entities as $term) {
      $uid = $this->getTermHash($term);
      $fallback_label = isset($term['label']) ? $term['label'] : '';
      $label = '';
      if (!empty($translated_labels[$uid])) {
        $label = $translated_labels[$uid];
      }
      elseif (!empty($fallback_label)) {
        $label = $fallback_label;
      }
      if (!empty($label) && !in_array($label, $tags) && mb_detect_encoding($label, 'UTF-8') && $this->getTermByName('tags', $label)) {
        $tags[] = $label;
        $count++;
      }
      if ($count >= 5) {
        break;
      }
    }

    return $tags;
  }

}
