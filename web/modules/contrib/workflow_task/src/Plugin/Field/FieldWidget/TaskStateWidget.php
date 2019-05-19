<?php

namespace Drupal\workflow_task\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflow_task\Plugin\Field\TaskStateFieldItemList;
use Drupal\workflow_task\StateTransitionValidationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'task_state_default' widget.
 *
 * @FieldWidget(
 *   id = "task_state_default",
 *   label = @Translation("Task state"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class TaskStateWidget extends OptionsSelectWidget implements ContainerFactoryPluginInterface {

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Moderation state transition validation service.
   *
   * @var \Drupal\workflow_task\StateTransitionValidationInterface
   */
  protected $validator;

  /**
   * Constructs a new TaskStateWidget object.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Field settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\workflow_task\StateTransitionValidationInterface $validator
   *   Task state transition validation service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, StateTransitionValidationInterface $validator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->validator = $validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('workflow_task.state_transition_validation')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\workflow_task\Entity\WorkflowTaskInterface $entity */
    $entity = $items->getEntity();

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $entity->getWorkflow();
    $default = $items->get($delta)->value ? $workflow->getTypePlugin()->getState($items->get($delta)->value) : $workflow->getTypePlugin()->getInitialState($entity);

    /** @var \Drupal\workflows\Transition[] $transitions */
    $transitions = $this->validator->getValidTransitions($entity, $this->currentUser);

    $transition_labels = [];
    $default_value = NULL;
    foreach ($transitions as $transition) {
      $transition_to_state = $transition->to();
      $transition_labels[$transition_to_state->id()] = $transition_to_state->label();
    }

    $element += [
      '#type' => 'container',
      'current' => [
        '#type' => 'item',
        '#title' => $this->t('Current state'),
        '#markup' => $default->label(),
        '#access' => !$entity->isNew(),
        '#wrapper_attributes' => [
          'class' => ['container-inline'],
        ],
      ],
      'state' => [
        '#type' => 'select',
        '#title' => $entity->isNew() ? $this->t('Save as') : $this->t('Change to'),
        '#key_column' => $this->column,
        '#options' => $transition_labels,
        '#default_value' => $default_value,
        '#access' => !empty($transition_labels),
        '#wrapper_attributes' => [
          'class' => ['container-inline'],
        ],
      ],
    ];
    $element['#element_validate'][] = [get_class($this), 'validateElement'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    $form_state->setValueForElement($element, [$element['state']['#key_column'] => $element['state']['#value']]);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return is_a($field_definition->getClass(), TaskStateFieldItemList::class, TRUE);
  }

}
