<?php

namespace Drupal\workflow_task\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * A computed field that provides a task entity's state.
 *
 * It links content entities to a workflow task state configuration entity via a
 * workflow task state content entity.
 */
class TaskStateFieldItemList extends FieldItemList {

  use ComputedItemListTrait {
    ensureComputedValue as traitEnsureComputedValue;
    get as traitGet;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $taskState = $this->getTaskStateId();
    // Do not store NULL values, in the case where an entity does not have a
    // task workflow associated with it, we do not create list items for
    // the computed field.
    if ($taskState) {
      // An entity can only have a single task state.
      $this->list[0] = $this->createItem(0, $taskState);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function ensureComputedValue() {
    // If the state field is set to an empty value, always recompute
    // the state. Empty is not a valid state value, when none is
    // present the default state is used.
    if (!isset($this->list[0]) || $this->list[0]->isEmpty()) {
      $this->valueComputed = FALSE;
    }
    $this->traitEnsureComputedValue();
  }

  /**
   * Gets the state ID linked to a content entity revision.
   *
   * @return string|null
   *   The moderation state ID linked to a content entity revision.
   */
  protected function getTaskStateId() {
    /** @var \Drupal\workflow_task\Entity\WorkflowTaskInterface $entity */
    $entity = $this->getEntity();

    if (!$entity->isNew() && $state = $entity->getState()) {
      return $state->id();
    }

    // It is possible that the bundle does not exist at this point. For example,
    // the node type form creates a fake Node entity to get default values.
    // @see \Drupal\node\NodeTypeForm::form()
    $workflow = $entity->getWorkflow();
    return $workflow ? $workflow->getTypePlugin()->getInitialState()->id() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    if ($index !== 0) {
      throw new \InvalidArgumentException('An entity can not have multiple moderation states at the same time.');
    }
    return $this->traitGet($index);
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($delta) {
    parent::onChange($delta);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);

    // If the parent created a field item and if the parent should be notified
    // about the change (e.g. this is not initialized with the current value),
    // update the moderated entity.
    if (isset($this->list[0]) && $notify) {
      $this->valueComputed = TRUE;
    }
  }

}
