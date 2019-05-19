<?php

namespace Drupal\workflow_moderation;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for moderation_information service.
 */
interface ModerationInformationInterface {

  /**
   * Loads the latest revision of a specific entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   */
  public function getLatestRevision($entity_type_id, $entity_id);

  /**
   * Returns the revision ID of the latest revision of the given entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return int
   *   The revision ID of the latest revision for the specified entity, or
   *   NULL if there is no such entity.
   */
  public function getLatestRevisionId($entity_type_id, $entity_id);

  /**
   * Returns the revision ID of the default revision for the specified entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return int
   *   The revision ID of the default revision, or NULL if the entity was
   *   not found.
   */
  public function getDefaultRevisionId($entity_type_id, $entity_id);

  /**
   * Determines if an entity is a latest revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A revisionable entity.
   *
   * @return bool
   *   TRUE if the specified object is the latest revision of its entity,
   *   FALSE otherwise.
   */
  public function isLatestRevision(EntityInterface $entity);

  /**
   * Determines if a forward revision exists for the specified entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity which may or may not have a forward revision.
   *
   * @return bool
   *   TRUE if this entity has forward revisions available, FALSE otherwise.
   */
  public function hasForwardRevision(EntityInterface $entity);

  /**
   * Get the workflow field machine name for the specified entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A revisionable entity..
   *
   * @return int
   *   Return the field name under that content type
   */
  public function getFieldName(EntityInterface $entity);

  /**
   * Determines if workflow state is published or not.
   *
   * @param string $workflowState
   *   The workflow state id.
   *
   * @return bool
   *   TRUE if this entity has forward revisions available, FALSE otherwise.
   */
  public function isPublishedState($workflowState);

  /**
   * Determines if revision is default for the specified entity.
   *
   * @param string $workflowState
   *   The workflow state id.
   *
   * @return bool
   *   TRUE if this revision is default, FALSE otherwise.
   */
  public function isDefaultRevision($workflowState);

  /**
   * Determines if a forward revision exists for the specified entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A revisionable entity..
   *
   * @return int
   *   Return the workflow id for content type
   */
  public function getWorkFlowId(EntityInterface $entity);

  /**
   * Determines if a forward revision exists for the specified entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A revisionable entity..
   *
   * @return int
   *   Return the last workflow state id for content type
   */
  public function getPreviousStateId(EntityInterface $entity);

  /**
   * Check for the specified entity assign any workflow.
   *
   *@param \Drupal\Core\Entity\EntityInterface $entity
   *   A revisionable entity..
   *
   * @return bool
   *   TRUE if entity having workflow, FALSE otherwise.
   */
  public function isModerateEntity(EntityInterface $entity);

  /**
   * Create node view on latest revision tab for the specified entity.
   *
   * @param int $entity_id
   *   The entity ID.
   *
   * @return var
   *   Return the html for node
   */
  public function loadTemplate($entity_id);

}
