<?php

namespace Drupal\trail_graph\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an example form.
 */
class TrailGraphNodeorderForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The current primary database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CacheTagsInvalidator $cache_tags_invalidator,
    CacheBackendInterface $cache_default,
    Connection $database
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->cacheDefault = $cache_default;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('cache_tags.invalidator'),
      $container->get('cache.default'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trail_graph_nodeorder_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, TermInterface $taxonomy_term = NULL) {
    $this->taxonomy_term = $taxonomy_term;

    if (!isset($taxonomy_term)) {
      return;
    }
    $node_ids = $this->database->select('taxonomy_index', 'ti')
      ->fields('ti', ['nid', 'weight'])
      ->condition('ti.tid', $taxonomy_term->id())
      ->orderBy('ti.weight')
      ->execute()
      ->fetchAllKeyed();
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple(array_keys($node_ids));

    $form['#title'] = $this->t('Order nodes for<em>%term_name</em>', ['%term_name' => $taxonomy_term->label()]);
    $form['#term_id'] = $taxonomy_term->id();

    $form['title'] = [
      '#type' => 'container',

    ];
    $form['title']['text'] = [
      '#type' => 'page_title',
      '#title' => $taxonomy_term->label(),
    ];

    $form['title']['link'] = [
      '#type' => 'link',
      '#title' => $this->t('Edit'),
      '#url' => Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $taxonomy_term->id(), 'mim' => 'trail_graph']),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'trail-graph--icon--edit',
        ],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => '90%',
        ]),
      ],
    ];

    $form['nodes'] = [
      '#type' => 'table',
      '#header' => [$this->t('Title'), $this->t('Weight')],
      '#empty' => $this->t('No nodes exist in this category.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'nodes-order-weight',
        ],
      ],
    ];

    foreach ($nodes as $id => $entity) {
      $form['nodes'][$id]['#attributes']['class'][] = 'draggable';
      $form['nodes'][$id]['#weight'] = $entity->id();
      $form['nodes'][$id]['label'] = [
        '#plain_text' => $entity->label(),
      ];

      $form['nodes'][$id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $entity->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $node_ids[$entity->id()],
        '#attributes' => ['class' => ['nodes-order-weight']],
      ];
    }
    $form['actions']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'trail-order-form-wrapper',
      ],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#ajax' => [
        'callback' => '::ajaxCancel',
        'wrapper' => 'trail-order-form-wrapper',
      ],
    ];

    $form['#prefix'] = '<div id="trail-order-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['#action'] = Url::fromRoute('trail_graph.trail_graph_nodeorder_form')->setRouteParameter('taxonomy_term', $taxonomy_term->id())->toString();

    if (!isset($this->form_state)) {
      $this->form = $form;
      $this->form_state = $form_state;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (isset($form_state->getTriggeringElement()['#attributes']['data-drupal-selector']) && $form_state->getTriggeringElement()['#attributes']['data-drupal-selector'] == 'edit-cancel') {
      return;
    }

    $nodes = $form_state->getValue('nodes');
    if (empty($nodes)) {
      $form_state->setErrorByName('nodes', $this->t('There are no nodes attached to current term.'));
    }
  }

  /**
   * Ajax callback for form submit.
   *
   * @param array $form
   *   Render array of form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array|AjaxResponse
   *   Returns same form or rebuilt form trough ajax response.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getErrors())) {
      if ($this->submitForm($form, $form_state)) {
        drupal_set_message($this->t('The node orders have been updated.'));
        $response = new AjaxResponse();
        $form = \Drupal::formBuilder()->rebuildForm($this->getFormId(), $form_state);
        $response->addCommand(new HtmlCommand('#trail-order-form-wrapper', $form));
        $response->addCommand(new InvokeCommand(NULL, 'resubmitTrailGraphFilterForm'));
        return $response;
      }
    }
    return $form;
  }

  /**
   * Cancel form ajax callback.
   *
   * @param array $form
   *   Render array of form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array|AjaxResponse
   *   Returns same form or rebuilt form trough ajax response.
   */
  public function ajaxCancel(array &$form, FormStateInterface $form_state) {
    if ($this->submitForm($form, $form_state, TRUE)) {
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $reset = FALSE) {
    if ($reset) {
      drupal_set_message($this->t('All changes are canceled'));
      return TRUE;
    }

    $tid = $form['#term_id'];
    $nodes = $form_state->getValue('nodes');
    $nids = array_keys($nodes);

    $entities = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    $tags = [];
    foreach ($entities as $nid => $node) {
      // Only take form elements that are blocks.
      if (is_array($nodes[$nid]) && array_key_exists('weight', $nodes[$nid])) {
        $this->database->update('taxonomy_index')
          ->fields(['weight' => $nodes[$nid]['weight']])
          ->condition('tid', $tid)
          ->condition('nid', $nid)
          ->execute();

        $tags = array_merge($tags, $node->getCacheTags());
      }
    }

    if (!empty($tags)) {
      $this->cacheTagsInvalidator->invalidateTags($tags);
    }

    $this->cacheDefault->deleteAll();

    return TRUE;
  }

}
