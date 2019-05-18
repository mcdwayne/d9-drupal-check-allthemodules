<?php

namespace Drupal\entity_list\Plugin\EntityListQuery;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
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
 *   id = "contextual_entity_list_query",
 *   label = @Translation("Contextual Entity Query")
 * )
 */
class ContextualEntityListQuery extends EntityListQueryBase {

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

  protected $entityFieldManager;

  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $bundle_info,
    LanguageManagerInterface $language_manager,
    EntityFieldManager $entity_field_manager,
    RouteMatchInterface $current_route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->languageManager = $language_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->routeMatch = $current_route_match;
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
      $container->get('language_manager'),
      $container->get('entity_field.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * Get all entity reference fields.
   *
   * @param string $entity_type_filter
   *   The entity type used to filter the field map.
   *
   * @return array
   *   A list of available entity_reference fields, ready to use as select
   *   options
   */
  protected function getAvailableFields($entity_type_filter = '') {
    $fields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
    $available_fields = [];
    foreach ($fields as $key => $bundle) {
      if (empty($entity_type_filter) || $key === $entity_type_filter) {
        $keys = array_keys($bundle);
        $available_fields[$key] = array_combine($keys, $keys);
      }
    }
    return (count($available_fields) == 1) ? reset($available_fields) : $available_fields;
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
    ], $this->settings['entity_type'] ?? '');

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => t('Entity type'),
      '#description' => $this->t('The entity type of the contextual entity to use.'),
      '#options' => $entity_types_options,
      '#multiple' => FALSE,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [
          get_class($form_state->getFormObject()),
          'update',
        ],
      ],
      '#ajax_update' => [
        'query-wrapper' => ['query_details', 'query'],
      ],
      '#empty_option' => $this->t('- Select an entity type -'),
      '#id' => 'select-entity-type',
      '#default_value' => $selected_entity_type,
    ];

    $form['bundle'] = [
      '#type' => 'select',
      '#title' => t('Bundle'),
      '#options' => [],
      '#required' => TRUE,
      '#multiple' => FALSE,
      '#empty_option' => $this->t('- Select a bundle -'),
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
        'bundle',
      ], $this->settings['bundle'] ?? '');
      $form['bundle']['#options'] = $bundle_options;
      $form['bundle']['#default_value'] = $selected_bundles;
    }

    $fields = $this->getAvailableFields($selected_entity_type);

    $default = $form_state->getValue([
      'query',
      'target',
    ], $this->settings['target']);

    $form['target'] = [
      '#type' => 'select',
      '#title' => $this->t('Field target'),
      '#description' => $this->t('The field using to get entities to display'),
      '#options' => $fields,
      '#default_value' => $default,
    ];

    $form['info'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('By default the contextual entity used to get the entities to display is the currently displayed entity (from the "current_route_match" service). Check the checkbox below to use the direct entity_list host entity.'),
    ];

    $form['use_host'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use host entity'),
      '#description' => $this->t('If you plan to check this option, be sure to use the entity list reference formatter on the entity list reference field that display the list.'),
      '#default_value' => $this->settings['use_host'] ?? FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildQuery() {
    // Do nothing here.
  }

  /**
   * Get the target field settings.
   *
   * @return array
   *   The target field settings or an empty array.
   */
  public function getTargetFieldSettings() {
    $fields = $this->entityFieldManager->getFieldDefinitions($this->settings['entity_type'] ?? '', $this->settings['bundle'] ?? '');
    if (!empty($this->settings['target'])) {
      /** @var \Drupal\field\Entity\FieldConfig $field */
      $field = $fields[$this->settings['target']] ?? NULL;
      if (!empty($field)) {
        return $field->getItemDefinition()->getSettings();
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    $settings = $this->getTargetFieldSettings();
    if (!empty($settings['target_type'])) {
      return $settings['target_type'];
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    $settings = $this->getTargetFieldSettings();
    if (!empty($settings['handler_settings']['target_bundles'])) {
      return $settings['handler_settings']['target_bundles'];
    }
    return [];
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
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function usePager() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function addTag($tag) {
    // Do nothing here.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTag($tag) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAllTags() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAnyTag() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function addMetaData($key, $object) {
    // Do nothing here.
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
    // Do nothing here.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($field, $langcode = NULL) {
    // Do nothing here.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function pager($limit = 10, $element = NULL) {
    // Do nothing here.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function range($start = NULL, $length = NULL) {
    // Do nothing here.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function sort($field, $direction = 'ASC', $langcode = NULL) {
    // Do nothing here.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->getReferencedEntities());
  }

  /**
   * {@inheritdoc}
   */
  public function accessCheck($access_check = TRUE) {
    // TODO: check access on the target entities.
    return $this;
  }

  /**
   * Get the host entity according to the plugin settings.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   An entity or NULL if no entity found.
   */
  public function getHostEntity() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $host */
    $host = NULL;
    if (isset($this->settings['use_host']) && $this->settings['use_host']) {
      $host = $this->entity->getHost();
    }
    else {
      $host = $this->routeMatch->getParameter($this->settings['entity_type']);
    }
    return $host;
  }

  /**
   * Get the referenced entities according to the field target.
   *
   * @return array
   *   An array of entities.
   */
  public function getReferencedEntities() {
    // Get referenced entities.
    $host = $this->getHostEntity();
    $fields = $this->settings['target'];
    if (!empty($host) && !empty($fields) && $host->hasField($fields)) {
      return array_map(function (EntityInterface $entity) {
        return $entity->id();
      }, $host->{$fields}->referencedEntities());
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $entities = $this->getReferencedEntities();
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function andConditionGroup() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function orConditionGroup() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($field, $langcode = NULL) {
    // Do nothing here.
    return $this;
  }

}
