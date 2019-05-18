<?php

namespace Drupal\entity_ui\EntityHandler;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Menu\LocalTaskInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\entity_ui\Plugin\EntityTabContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Provides a listing of Entity tab entities for a single target entity type.
 *
 * This inherits from DraggableListBuilder, as that does a fair amount of work
 * for us in making this a form to save weights, but removes the actual
 * draggability as core's tabledrag library doesn't support fixed rows.
 *
 * @todo: implement draggability with custom JS, or add the feature to core.
 */
class EntityTabListBuilder extends DraggableListBuilder {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Entity Tab content plugin manager
   *
   * @var \Drupal\entity_ui\Plugin\EntityTabContentManager
   */
  protected $entityTabContentPluginManager;

  /**
   * The menu local task plugin manager
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskManager;

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The target entity type ID.
   */
  protected $target_entity_type_id;

  /**
   * Constructs a new EntityTabListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\entity_ui\Plugin\EntityTabContentManager
   *   The entity tab plugin manager.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $local_task_manager
   *   The local task manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The currently active route match object.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The dynamic router service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    EntityTabContentManager $entity_tab_content_manager,
    LocalTaskManagerInterface $local_task_manager,
    RouteMatchInterface $current_route_match,
    RequestMatcherInterface $router
    ) {
    parent::__construct($entity_type, $storage);

    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->entityTabContentPluginManager = $entity_tab_content_manager;
    $this->localTaskManager = $local_task_manager;
    $this->currentRouteMatch = $current_route_match;
    $this->router = $router;

    $this->target_entity_type_id = $current_route_match->getRouteObject()->getOption('_target_entity_type_id');

    $this->target_entity_canonical_path = $this->entityTypeManager
      ->getDefinition($this->target_entity_type_id)
      ->getLinkTemplate('canonical');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('plugin.manager.entity_ui_tab_content'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('current_route_match'),
      $container->get('router')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_ui_collection';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    // No need to sort; load() does that.
    $query = $this->storage->getQuery();
    $query->condition('target_entity_type', $this->target_entity_type_id);

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];

    $header['label'] = $this->t('Entity tab name');
    $header['path'] = $this->t('Path');
    $header['plugin_label'] = $this->t('Content provider');

    // Let the parent method add the weight.
    $header += parent::buildHeader();

    $header['operations'] = $this->t('Operations');

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    // Parent class tries to be too helpful and stupidly attempts to turn this
    // cell into a markup element...
    $row['label'] = $entity->label();

    $row['path'] = [
      '#markup' => '…/' . $entity->getPathComponent(),
    ];

    $plugin_definition = $this->entityTabContentPluginManager->getDefinition($entity->getPluginID());
    $row['plugin_label'] = [
      '#markup' => $plugin_definition['label'],
    ];

    // Let the parent method add the weight.
    $row += parent::buildRow($entity);

    // Change the weight element to a textfield, as some hardcoded tabs have
    // very large weights.
    $row['weight']['#type'] = 'textfield';
    $row['weight']['#size'] = 3;

    $row['operations']['data'] = $this->buildOperations($entity);

    $row['#weight'] = $entity->get($this->weightKey);

    return $row;
  }

  /**
   * Builds the row for a hardcoded local task, i.e. from another module.
   *
   * @param \Drupal\Core\Menu\LocalTaskInterface $task_plugin
   *  The task plugin for the tab.
   *
   * @return
   *  A table row render element.
   */
  protected function buildPluginLockedRow(LocalTaskInterface $task_plugin) {
    $row = [];
    $row['label'] = ['#markup' => $task_plugin->getTitle()];

    $definition = $task_plugin->getPluginDefinition();
    $route = $this->router->getRouteCollection()->get($definition['route_name']);
    $path = $route->getPath();

    $base_path = $this->target_entity_canonical_path . '/';
    if ($path == $this->target_entity_canonical_path) {
      $component = '…/';
    }
    elseif (strpos($path, $base_path) !== FALSE) {
      $component = '…/' . substr($path, strlen($base_path));
    }
    else {
      $component = $this->t('(non-standard path)');
    }

    $row['path'] = [
      '#markup' => $component,
    ];


    $row['plugin_label'] = [
      '#markup' => $this->moduleHandler->getName($definition['provider'])
        . ' ' . $this->t('(built-in)'),
    ];

    // Add weight column.
    $row['weight'] = [
      '#type' => 'textfield',
      '#title' => t('Weight for @title', ['@title' => $task_plugin->getTitle()]),
      '#title_display' => 'invisible',
      '#default_value' => $task_plugin->getWeight(),
      '#size' => 3,
      '#disabled' => TRUE,
      '#attributes' => ['class' => ['weight']],
    ];

    $row['operations'] = ['#markup' => '-'];

    $row['#weight'] = $task_plugin->getWeight();

    return $row;
  }

  /**
   * Builds the row for a canonical route that has no local task.
   *
   * @return
   *  A table row render element.
   */
  protected function buildCanonicalLockedRow() {
    $row = [];
    $row['label'] = ['#markup' => $this->t("Canonical route")];

    $row['path'] = [
      '#markup' => 'TODO',
    ];

    $provider = $this->entityTypeManager->getDefinition($this->target_entity_type_id)->getProvider();
    $row['plugin_label'] = [
      '#markup' => $this->moduleHandler->getName($provider)
        . ' ' . $this->t('(built-in)'),
    ];

    // Add weight column.
    $row['weight'] = [
      '#type' => 'textfield',
      '#title' => t('Weight for canonical route tab'),
      '#title_display' => 'invisible',
      '#default_value' => 0,
      '#size' => 3,
      '#disabled' => TRUE,
      '#attributes' => ['class' => ['weight']],
    ];

    $row['operations'] = ['#markup' => '-'];

    $row['#weight'] = 0;

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Get existing tasks to show in the form as fixed rows.
    $tabs = $this->localTaskManager->getLocalTasksForRoute("entity.{$this->target_entity_type_id}.canonical");
    $canonical_tab_id = NULL;
    if (isset($tabs[0])) {
      foreach ($tabs[0] as $plugin_id => $task_plugin) {
        if (substr($plugin_id, 0, 36) == 'entity_ui.target_entity_local_tasks:') {
          // Skip tabs which are ours.
          continue;
        }

        $definition = $task_plugin->getPluginDefinition();
        if ($definition['route_name'] == "entity.{$this->target_entity_type_id}.canonical") {
          $canonical_tab_id = $plugin_id;
        }

        // It's ok to add things into this form element that are not entities, as
        // submitForm() checks that a form key corresponts to a ID key in
        // $this->entities. Prefix the form key to prevent the case where an
        // entity tab entity's ID happens to match a hardcoded plugin.
        $form[$this->entitiesKey]['tab:' . $plugin_id] = $this->buildPluginLockedRow($task_plugin);
      }
    }

    // If no other module defines local tasks, then the canonical route doesn't
    // have a default local task. In this case, we should still represent the
    // canonical route as a fixed tab, since we'll take care of providing the
    // local task for it.
    if (empty($canonical_tab_id)) {
      $form[$this->entitiesKey]['route:canonical'] = $this->buildCanonicalLockedRow();
    }

    // Order the rows by the weight property.
    // (The table render element doesn't recognize the weight property on rows.)
    uasort($form[$this->entitiesKey], [SortArray::class, 'sortByWeightProperty']);

    // Tweak the empty text.
    $form[$this->entitiesKey]['#empty'] = $this->t('There is no @label for @target_type_label entities yet.', [
      '@label' => $this->entityType->getLabel(),
      '@target_type_label' => $this->entityTypeManager->getDefinition($this->target_entity_type_id)->getLabel(),
    ]);

    // Remove the draggability: draggable tables don't support fixed rows.
    // TODO: implement custom JS to support this.
    unset($form[$this->entitiesKey]['#tabledrag']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Tab weights may have changed, so clear local task caches.
    $this->localTaskManager->clearCachedDefinitions();
  }

}
