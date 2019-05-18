<?php

namespace Drupal\fac\Plugin\Search;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\fac\FacConfigInterface;
use Drupal\fac\SearchBase;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\QueryHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides a Search API search plugin.
 *
 * @Search(
 *   id = "SearchApiSearch",
 *   name = @Translation("SearchAPI search plugin"),
 * )
 */
class SearchApiSearch extends SearchBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Search Api query helper service.
   *
   * @var \Drupal\search_api\Utility\QueryHelperInterface
   */
  protected $queryHelper;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * SearchApiSearch constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\search_api\Utility\QueryHelperInterface $query_helper
   *   The Search Api query helper service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, QueryHelperInterface $query_helper, TranslationInterface $string_translation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->queryHelper = $query_helper;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('search_api.query_helper'),
      $container->get('string_translation')
    );
  }

  /**
   * Gets the configuration form for the search plugin.
   *
   * @param array $plugin_config
   *   The plugin config array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The configuration form for the search plugin.
   */
  public function getConfigForm(array $plugin_config, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $values_index_id = isset($input['plugin']['config']['index']) ? $input['plugin']['config']['index'] : '';
    $config_index_id = isset($plugin_config['index']) ? $plugin_config['index'] : '';

    $index_options = [];
    try {
      $query = $this->entityTypeManager->getStorage('search_api_index')->getQuery();
      $index_ids = $query->execute();
      $indexes = $this->entityTypeManager->getStorage('search_api_index')->loadMultiple($index_ids);
    }
    catch (InvalidPluginDefinitionException $e) {
      $indexes = [];
    }

    foreach ($indexes as $index) {
      if ($index->status()) {
        $index_options[$index->id()] = $index->label();
      }
    }
    $form['index'] = [
      '#type' => 'select',
      '#options' => $index_options,
      '#title' => $this->t('Select the index to use'),
      '#required' => TRUE,
      '#default_value' => isset($plugin_config['index']) ? $plugin_config['index'] : '',
      '#description' => $this->t('Only active indexes are shown. Missing an index? Check if the index is enabled in the <a href=":href">Search API configuration</a>.', [
        ':href' => '/admin/config/search/search-api',
      ]),
      '#ajax' => [
        'callback' => '::pluginSelection',
        'wrapper' => 'plugin-subform',
        'event' => 'change',
        'effect' => 'fade',
        'progress' => [
          'message' => $this->t('Loading search plugin options...'),
        ],
      ],
    ];

    $text_fields = [];
    $sort_fields = [
      'search_api_relevance' => $this->t('Relevance'),
      'search_api_id' => $this->t('Item ID'),
    ];

    if (!empty($values_index_id) || !empty($config_index_id)) {
      try {
        /* @var \Drupal\search_api\IndexInterface $index */
        $index = $this->entityTypeManager->getStorage('search_api_index')->load(!empty($values_index_id) ? $values_index_id : $config_index_id);
        $index_fields = $index->getFields(TRUE);

        $full_text_fields = $index->getFullTextFields();
        $sortable_types = [
          'string',
          'date',
        ];

        foreach ($index_fields as $index_field) {
          if (in_array($index_field->getFieldIdentifier(), $full_text_fields)) {
            $text_fields[$index_field->getFieldIdentifier()] = $index_field->getLabel();
          }

          if (in_array($index_field->getType(), $sortable_types)) {
            $sort_fields[$index_field->getFieldIdentifier()] = $index_field->getLabel();
          }
        }
      }
      catch (InvalidPluginDefinitionException $e) {
      }
    }

    $form['text_fields'] = [
      '#type' => 'select',
      '#options' => $text_fields,
      '#title' => $this->t('Full text fields to search through'),
      '#multiple' => TRUE,
      '#default_value' => isset($plugin_config['text_fields']) ? $plugin_config['text_fields'] : 'all',
      '#description' => $this->t('Select the full text fields to search through. No selection will result in searching through all fields.'),
    ];

    $form['sort_field'] = [
      '#type' => 'select',
      '#options' => $sort_fields,
      '#title' => $this->t('Select the sort field'),
      '#required' => TRUE,
      '#default_value' => isset($plugin_config['sort_field']) ? $plugin_config['sort_field'] : 'search_api_relevance',
      '#description' => $this->t('Select the field that the result is sorted by.'),
    ];

    $form['sort_direction'] = [
      '#type' => 'select',
      '#options' => [
        'ASC' => $this->t('Ascending'),
        'DESC' => $this->t('Descending'),
      ],
      '#title' => $this->t('Select the sort direction'),
      '#required' => TRUE,
      '#default_value' => isset($plugin_config['sort_direction']) ? $plugin_config['sort_direction'] : 'DESC',
      '#description' => $this->t('Select the sort direction.'),
    ];

    $form['language_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter by language'),
      '#default_value' => isset($plugin_config['language_filter']) ? $plugin_config['language_filter'] : '',
      '#description' => $this->t('Check this option if you want the results to be filtered by language'),
    ];

    $form['langcode_includes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('No specific language'),
      '#options' => [
        LanguageInterface::LANGCODE_NOT_APPLICABLE => $this->t('Include "language not applicable"'),
        LanguageInterface::LANGCODE_NOT_SPECIFIED => $this->t('Include "Language not specified"'),
      ],
      '#default_value' => isset($plugin_config['langcode_includes']) ? $plugin_config['langcode_includes'] : [],
      '#states' => [
        'invisible' => [
          ':input[name="plugin[config][language_filter]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback on index selection.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   *
   * @return array
   *   Part of the form to replace.
   */
  public function searchApiSearchIndexSelection(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Return the results for the given key.
   *
   * @param \Drupal\fac\FacConfigInterface $fac_config
   *   The FacConfig entity.
   * @param string $langcode
   *   The language code.
   * @param string $key
   *   The query string to get results for.
   *
   * @return array
   *   The result entity ids for the given key.
   */
  public function getResults(FacConfigInterface $fac_config, $langcode, $key) {
    $results = [];

    $plugin_config = $fac_config->getSearchPluginConfig();
    try {
      /* @var \Drupal\search_api\IndexInterface $index */
      $index = $this->entityTypeManager->getStorage('search_api_index')->load($plugin_config['index']);
    }
    catch (InvalidPluginDefinitionException $e) {
      return $results;
    }

    $query = $this->queryHelper->createQuery($index);
    $query->addTag('fac');
    $query->addTag('fac_' . $fac_config->id());

    $query->keys($key);

    if ($plugin_config['text_fields']) {
      $query->setFulltextFields($plugin_config['text_fields']);
    }

    if ($plugin_config['language_filter']) {
      $langcodes = array_filter($plugin_config['langcode_includes']);
      $langcodes[] = $langcode;
      $query->setLanguages($langcodes);
    }

    $query->range(0, $fac_config->getNumberOfResults());
    $query->sort($plugin_config['sort_field'], $plugin_config['sort_direction']);

    try {
      $items = $query->execute()->getResultItems();

      foreach ($items as $item) {
        $entity = $item->getOriginalObject()->getValue();

        $results[] = [
          'entity_type' => $entity->getEntityTypeId(),
          'entity_id' => $entity->id(),
        ];
      }
    }
    catch (SearchApiException $e) {
      return $results;
    }

    return $results;
  }

}
