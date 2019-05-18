<?php

namespace Drupal\entity_list\Plugin\EntityListQuery;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\entity_list\Plugin\EntityListQueryBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DefaultEntityListQuery.
 *
 * Use a Drupal\Core\Entity\Query\QueryInterface implementation by default.
 *
 * @package Drupal\entity_list\Plugin
 *
 * @EntityListQuery(
 *   id = "default_entity_list_query",
 *   label = @Translation("Entity Query")
 * )
 */
class DefaultEntityListQuery extends EntityListQueryBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current query object.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The language manager service used to get available languages.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->languageManager = $language_manager;
    $this->query = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state) {
    $form = [];

    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_filter($entity_types, function ($element) {
      return $element instanceof ContentEntityTypeInterface;
    });
    $entity_types_options = [];
    foreach ($entity_types as $key => $entity_type) {
      $entity_types_options[$key] = $entity_type->getLabel();
    }

    $selected_entity_type = $form_state->getValue([
      'query',
      'entity_type',
    ], $this->getEntityTypeId());

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => t('Entity type'),
      '#options' => $entity_types_options,
      '#required' => TRUE,
      '#multiple' => FALSE,
      '#ajax' => [
        'callback' => [
          get_class($form_state->getFormObject()),
          'update',
        ],
      ],
      '#ajax_update' => [
        'query-wrapper' => ['query_details', 'query'],
        'display-wrapper' => ['display_details', 'display'],
      ],
      '#empty_option' => $this->t('- Select an entity type -'),
      '#id' => 'select-entity-type',
      '#default_value' => $selected_entity_type,
    ];

    $form['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => t('Bundles'),
      '#options' => [],
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#ajax' => [
        'callback' => [
          get_class($form_state->getFormObject()),
          'update',
        ],
      ],
      '#ajax_update' => [
        'display-wrapper' => ['display_details', 'display'],
      ],
      '#id' => 'select-bundles',
      '#prefix' => '<div id="bundles-wrapper">',
      '#suffix' => '</div>',
    ];

    if (!empty($selected_entity_type) && isset($entity_types[$selected_entity_type])) {
      $bundles = $this->bundleInfo->getBundleInfo($entity_types[$selected_entity_type]->id());
      $bundle_options = [];
      foreach ($bundles as $machine_name => $bundle) {
        $bundle_options[$machine_name] = $bundle['label'];
      }
      $selected_bundles = $form_state->getValue([
        'query',
        'bundles',
      ], $this->getBundles());
      $form['bundles']['#options'] = $bundle_options;
      $form['bundles']['#default_value'] = $selected_bundles;
    }

    $languages = [];
    $available_languages = $this->languageManager->getLanguages();
    foreach ($available_languages as $id => $available_language) {
      $languages[$id] = $available_language->getName();
    }
    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#description' => $this->t('Choose the language. Select auto to automatically choose language from current viewed page language.'),
      '#options' => array_merge($languages, ['auto' => $this->t('Auto')]),
      '#empty_option' => $this->t('- None -'),
      '#disabled' => empty($languages),
      '#default_value' => $form_state->getValue([
        'query',
        'language',
      ], $this->getLanguage()),
    ];

    $form['items_per_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of items per page.'),
      '#description' => $this->t('Leave blank to display all items.'),
      '#default_value' => $form_state->getValue([
        'query',
        'items_per_page',
      ], $this->getItemsPerPage()),
      '#size' => 2,
    ];

    $form['use_pager'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use pager'),
      '#default_value' => $form_state->getValue([
        'query',
        'use_pager',
      ], $this->usePager()),
      '#states' => [
        'visible' => [
          ':input[name="query[items_per_page]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildQuery() {
    if (($entity_type = $this->getEntityTypeId())) {
      try {
        $storage = $this->entityTypeManager->getStorage($entity_type);
        $this->query = $storage->getQuery();
      }
      catch (InvalidPluginDefinitionException $e) {
      }
      if (!empty($this->query)) {
        $bundle_condition_key = $this->entityTypeManager->getDefinition($this->getEntityTypeId())
          ->getKey('bundle');
        if (!empty($bundle_condition_key)) {
          $bundles_condition = $this->orConditionGroup();
          foreach ($this->getBundles() as $bundle) {
            $bundles_condition->condition($bundle_condition_key, $bundle);
          }
          $this->condition($bundles_condition);
        }
        $langcode = $this->getLanguage();
        if ($langcode == 'auto') {
          $langcode = $this->languageManager->getCurrentLanguage()->getId();
        }
        if (!empty($langcode)) {
          $this->condition('langcode', $langcode);
        }
        $items_per_page = $this->getItemsPerPage();
        if (!empty($items_per_page)) {
          if ($this->usePager()) {
            $this->pager($items_per_page);
          }
          else {
            $this->range(0, $items_per_page);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return ($this->query) ? $this->query->getEntityTypeId() : $this->settings['entity_type'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    return $this->settings['bundles'] ?? [];
  }

  /**
   * Get the selected language.
   *
   * @return string
   *   The language id or empty.
   */
  public function getLanguage() {
    return $this->settings['language'] ?? '';
  }

  /**
   * Get the number of entities to queries.
   *
   * @return string
   *   The number od entities to queries, empty to get all entities.
   */
  public function getItemsPerPage() {
    return $this->settings['items_per_page'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function usePager() {
    return $this->entity->get('query')['use_pager'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function addTag($tag) {
    $this->query->addTag($tag);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTag($tag) {
    return $this->query->hasTag($tag);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAllTags() {
    return $this->query->hasAllTags();
  }

  /**
   * {@inheritdoc}
   */
  public function hasAnyTag() {
    return $this->query->hasAnyTag();
  }

  /**
   * {@inheritdoc}
   */
  public function addMetaData($key, $object) {
    $this->query->addMetaData($key, $object);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaData($key) {
    return $this->query->getMetaData($key);
  }

  /**
   * {@inheritdoc}
   */
  public function condition($field, $value = NULL, $operator = NULL, $langcode = NULL) {
    $this->query->condition($field, $value, $operator, $langcode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($field, $langcode = NULL) {
    $this->query->exists($field, $langcode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function pager($limit = 10, $element = NULL) {
    $this->query->pager($limit, $element);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function range($start = NULL, $length = NULL) {
    $this->query->range($start, $length);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function sort($field, $direction = 'ASC', $langcode = NULL) {
    $this->query->sort($field, $direction, $langcode);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    $this->query->count();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function accessCheck($access_check = TRUE) {
    $this->query->accessCheck($access_check);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this->query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function andConditionGroup() {
    return $this->query->andConditionGroup();
  }

  /**
   * {@inheritdoc}
   */
  public function orConditionGroup() {
    return $this->query->orConditionGroup();
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($field, $langcode = NULL) {
    $this->query->notExists($field, $langcode);
    return $this;
  }

}
