<?php

namespace Drupal\drd\Plugin\AdvancedQueue\JobType;

/**
 * Provides an AdvancedQueue JobType for DRD Entities.
 *
 * @AdvancedQueueJobType(
 *  id = "drd_action_entity",
 *  label = @Translation("DRD Entity Action"),
 * )
 */
class ActionEntity extends Action {

  /**
   * {@inheritdoc}
   */
  public function processAction() {
    /** @var \Drupal\drd\Plugin\Action\BaseEntityInterface $action */
    $action = $this->action;
    /** @var \Drupal\drd\Entity\BaseInterface $entity */
    $entity = \Drupal::entityTypeManager()->getStorage($this->payload['entity_type'])->load($this->payload['entity_id']);

    return $action->executeAction($entity);
  }

}
