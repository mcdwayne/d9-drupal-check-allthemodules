<?php

namespace Drupal\workflow_task\Plugin\WorkflowType;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workflow_task\WorkflowTaskState;
use Drupal\workflows\Plugin\WorkflowTypeBase;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Attaches workflows to content entity types and their bundles.
 *
 * @WorkflowType(
 *   id = "workflow_task",
 *   label = @Translation("Task based workflow"),
 *   required_states = {
 *     "to_do",
 *     "done",
 *   },
 *   forms = {
 *     "configure" = "\Drupal\workflow_task\Form\TaskWorkflowTypeConfigurationForm",
 *     "state" = "\Drupal\workflow_task\Form\TaskWorkflowTypeStateForm"
 *   },
 * )
 */
class TaskWorkflowType extends WorkflowTypeBase implements TaskWorkflowTypeInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a TaskWorkflowType object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
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
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getState($state_id) {
    return new WorkflowTaskState(parent::getState($state_id));
  }

  /**
   * {@inheritdoc}
   */
  public function workflowHasData(WorkflowInterface $workflow) {
    return (bool) $this->entityTypeManager
      ->getStorage('workflow_task')
      ->getQuery()
      ->condition('workflow', $workflow->id())
      ->count()
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function workflowStateHasData(WorkflowInterface $workflow, StateInterface $state) {
    return (bool) $this->entityTypeManager
      ->getStorage('workflow_task')
      ->getQuery()
      ->condition('workflow', $workflow->id())
      ->condition('state', $state->id())
      ->count()
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'states' => [
        'to_do' => [
          'label' => 'To Do',
          'weight' => 0,
        ],
        'in_progress' => [
          'label' => 'In Progress',
          'weight' => 1,
        ],
        'done' => [
          'label' => 'Done',
          'weight' => 2,
        ],
      ],
      'transitions' => [
        'finish' => [
          'label' => 'Finish',
          'to' => 'done',
          'weight' => 0,
          'from' => [
            'to_do',
            'in_progress',
          ],
        ],
        'start' => [
          'label' => 'Start',
          'to' => 'in_progress',
          'weight' => 1,
          'from' => [
            'to_do',
          ],
        ],
        'reopen' => [
          'label' => 'Reopen',
          'to' => 'to_do',
          'weight' => 1,
          'from' => [
            'done',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    // When modules that provide entity types are removed, ensure they are also
    // removed from the workflow.
    if (!empty($dependencies['module'])) {
      // Gather all entity definitions provided by the dependent modules which
      // are being removed.
      $module_entity_definitions = [];
      foreach ($this->entityTypeManager->getDefinitions() as $entity_definition) {
        if (in_array($entity_definition->getProvider(), $dependencies['module'])) {
          $module_entity_definitions[] = $entity_definition;
        }
      }
    }

    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    // Ensure that states and entity types are ordered consistently.
    ksort($configuration['states']);
    return $configuration;
  }

}
