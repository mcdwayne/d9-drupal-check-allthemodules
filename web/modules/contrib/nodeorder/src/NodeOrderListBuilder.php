<?php

namespace Drupal\nodeorder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Database\Query\PagerSelectExtender;

/**
 * Defines a class to build a listing of node entities.
 */
class NodeOrderListBuilder extends EntityListBuilder implements FormInterface {

  /**
   * The key to use for the form element containing the entities.
   *
   * @var string
   */
  protected $entitiesKey = 'entities';

  /**
   * The entities being listed.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities = [];

  /**
   * Current taxonomy term.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $taxonomyTerm;

  /**
   * The current primary database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  protected $cacheTagsInvalidator;

  /**
   * Default cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheDefault;

  /**
   * Nodes weight.
   *
   * @var array
   */
  private $nodesWeight = [];

  /**
   * Selectable entities total number.
   *
   * @var int
   */
  private $entitiesCount = 0;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, Connection $database, FormBuilderInterface $form_builder, CacheTagsInvalidator $cache_tags_invalidator, CacheBackendInterface $cache_default, Term $taxonomy_term) {
    parent::__construct($entity_type, $storage);

    $this->database = $database;
    $this->formBuilder = $form_builder;
    $this->taxonomyTerm = $taxonomy_term;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->cacheDefault = $cache_default;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type, Term $taxonomy_term = NULL) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('database'),
      $container->get('form_builder'),
      $container->get('cache_tags.invalidator'),
      $container->get('cache.default'),
      $taxonomy_term
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $taxonomy_indexes = $this->database->select('taxonomy_index', 'ti');
    $taxonomy_indexes->condition('ti.tid', $this->taxonomyTerm->id());

    $count_query = clone $taxonomy_indexes;
    $count_query->addExpression('Count(ti.nid)');

    /** @var \Drupal\Core\Database\Query\PagerSelectExtender $paged_query */
    $paged_query = $taxonomy_indexes->extend(PagerSelectExtender::class);
    $paged_query->limit($this->limit);
    $paged_query->setCountQuery($count_query);

    $paged_query->fields('ti', ['nid', 'weight']);
    $paged_query->orderBy('ti.weight');

    $this->nodesWeight = $paged_query->execute()->fetchAllKeyed();

    $this->entitiesCount = (int) $count_query->execute()->fetchField();

    // Method load will be triggered later, if we pass empty array as an argument,
    // method will be load all entities with type node, will be passed -1 as an
    // allowed ID for node to prevent loading all entities.
    // Case for term without nodes.
    return $this->nodesWeight ? array_keys($this->nodesWeight) : [-1];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nodeorder_list_builder';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'title' => $this->t('Title'),
      'type' => [
        'data' => $this->t('Content type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'status' => $this->t('Status'),
    ];
    $header['weight'] = $this->t('Weight');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity, $weight_delta = 0) {
    /** @var \Drupal\node\NodeInterface $entity */
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $entity->toUrl(),
    ];

    /** @var \Drupal\node\Entity\NodeType $type */
    $type = $entity->type->entity;
    $row['type'] = ['#markup' => $type->label()];

    $published = $entity->isPublished() ? $this->t('Published') : $this->t('Not published');
    $row['status'] = ['#markup' => $published];

    $row['#attributes']['class'][] = 'draggable';
    $row['#weight'] = $this->nodesWeight[$entity->id()];
    $row['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for @title', ['@title' => $entity->label()]),
      '#title_display' => 'invisible',
      '#delta' => $weight_delta,
      '#default_value' => $this->nodesWeight[$entity->id()],
      '#attributes' => ['class' => ['weight']],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Order nodes for <em>%term_name</em>', ['%term_name' => $this->taxonomyTerm->label()]);

    $form['term_id'] = [
      '#type' => 'value',
      '#value' => $this->taxonomyTerm->id(),
    ];

    $form[$this->entitiesKey] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => $this->t('There is no @label yet.', ['@label' => $this->entityType->getLabel()]),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
    ];

    $this->entities = $this->load();
    $weight_delta = ceil($this->entitiesCount / 2);

    foreach ($this->entities as $entity) {
      if ($row = $this->buildRow($entity, $weight_delta)) {
        $form[$this->entitiesKey][$entity->id()] = $row;
      }
    }

    if ($this->limit) {
      $form['pager'] = [
        '#type' => 'pager',
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('term_id'))) {
      $form_state->setError($form, $this->t('Term ID required.'));
    }

    if (empty($form_state->getValue($this->entitiesKey))) {
      $form_state->setErrorByName($this->entitiesKey, $this->t('There are no nodes attached to current term.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term_id = $form_state->getValue('term_id');
    $nodes = $form_state->getValue($this->entitiesKey);
    $nids = array_keys($nodes);

    $tags = [];
    $entities = $this->storage->loadMultiple($nids);
    foreach ($entities as $nid => $node) {
      // Only take form elements that are blocks.
      if (isset($this->entities[$nid]) && $this->nodesWeight[$nid] !== $nodes[$nid]['weight']) {
        $this->database->update('taxonomy_index')
          ->fields(['weight' => $nodes[$nid]['weight']])
          ->condition('tid', $term_id)
          ->condition('nid', $nid)
          ->execute();

        $tags = array_merge($tags, $node->getCacheTags());
      }
    }

    drupal_set_message($this->t('The node orders have been updated.'));

    if (!empty($tags)) {
      $this->cacheTagsInvalidator->invalidateTags($tags);
    }

    $this->cacheDefault->deleteAll();
  }

}
