<?php

namespace Drupal\workflow_task\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if a task state transition is valid.
 */
class TaskStateConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Creates a new TaskStateConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\workflow_task\Entity\WorkflowTaskInterface $entity */
    $entity = $value->getEntity();

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $entity->getWorkflow();

    if (!$workflow->getTypePlugin()->hasState($entity->getStateId())) {
      $this->context->addViolation($constraint->invalidStateMessage, [
        '%state' => $entity->getStateId(),
        '%workflow' => $workflow->label(),
      ]);
      return;
    }

    if (!$entity->isNew()) {
      /** @var \Drupal\workflow_task\Entity\WorkflowTaskInterface $original_entity */
      $original_entity = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->loadRevision($entity->getLoadedRevisionId());
      if (!$entity->isDefaultTranslation() && $original_entity->hasTranslation($entity->language()->getId())) {
        $original_entity = $original_entity->getTranslation($entity->language()->getId());
      }

      if (!$workflow->getTypePlugin()->hasState($original_entity->getStateId())) {
        return;
      }

      $new_state = $workflow->getTypePlugin()->getState($entity->getStateId());
      $original_state = $workflow->getTypePlugin()->getState($original_entity->getStateId());

      if (!$original_state->canTransitionTo($new_state->id())) {
        $this->context->addViolation($constraint->message, [
          '%from' => $original_state->label(),
          '%to' => $new_state->label()
        ]);
      }
    }
  }

}
