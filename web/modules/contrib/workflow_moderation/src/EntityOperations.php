<?php

namespace Drupal\workflow_moderation;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\workflow_moderation\Form\WorkflowModerationForm;

/**
 * Defines a class for reacting to entity events.
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The Moderation Information service.
   *
   * @var \Drupal\workflow_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * The Revision Tracker service.
   *
   * @var \Drupal\workflow_moderation\RevisionTracker
   */
  protected $tracker;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\workflow_moderation\ModerationInformationInterface $moderation_info
   *   Moderation information service.
   * @param \Drupal\workflow_moderation\RevisionTracker $tracker
   *   The revision tracker.
   */
  public function __construct(ModerationInformationInterface $moderation_info, RevisionTracker $tracker) {
    $this->moderationInfo = $moderation_info;
    $this->tracker = $tracker;
  
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workflow_moderation.moderation_information'),
      $container->get('workflow_moderation.revision_tracker')
    );
  
  }

  /**
   * Acts on an entity and set published status based on the moderation state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  public function entityPresave(EntityInterface $entity) {
    if (!$this->moderationInfo->isModerateEntity($entity)) {
      return FALSE;
    }
    $workflowFieldName    = $this->moderationInfo->getFieldName($entity);
    $newWorkflowState     = $entity->$workflowFieldName->value;
    $currentWorkflowState = $this->moderationInfo->getPreviousStateId($entity);
    $nodeStatus           = ($this->moderationInfo->isPublishedState($newWorkflowState)) ? $entity->setPublished() : $entity->setUnpublished();
    if (!empty($currentWorkflowState) && $this->moderationInfo->isPublishedState($currentWorkflowState) && !$this->moderationInfo->isPublishedState($newWorkflowState)) {
        $entity->isDefaultRevision($this->moderationInfo->isDefaultRevision($newWorkflowState));
    }
    else {
        $entity->isDefaultRevision($this->moderationInfo->isDefaultRevision($newWorkflowState));
    }
  
  }

  /**
   * Hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just saved.
   *
   * @see hook_entity_insert()
   */
  public function entityInsert(EntityInterface $entity) {
    if (!$this->moderationInfo->isModerateEntity($entity)) {
      return FALSE;
    }
    if ($entity instanceof Node) {
      $this->setLatestRevision($entity);
    }
  
  }

  /**
   * Hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just saved.
   *
   * @see hook_entity_update()
   */
  public function entityUpdate(EntityInterface $entity) {
    if (!$this->moderationInfo->isModerateEntity($entity)) {
      return FALSE;
    }
    if ($entity instanceof Node) {
      $this->setLatestRevision($entity);
    }
  
  }

  /**
   * Set the latest revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The content entity for.
   */
  protected function setLatestRevision(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $this->tracker->setLatestRevision(
      $entity->getEntityTypeId(),
      $entity->id(),
      $entity->language()->getId(),
      $entity->getRevisionId()
    );
  
  }

}
