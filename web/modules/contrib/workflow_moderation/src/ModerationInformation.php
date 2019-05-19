<?php

namespace Drupal\workflow_moderation;

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workflow\Entity\WorkflowState;

/**
 * General service for moderation-related questions about Entity API.
 */
class ModerationInformation implements ModerationInformationInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Creates a new ModerationInformation instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle information service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager = NULL, EntityTypeBundleInfoInterface $bundle_info = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;

  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevision($entity_type_id, $entity_id) {
    if ($latest_revision_id = $this->getLatestRevisionId($entity_type_id, $entity_id)) {
      return $this->entityTypeManager->getStorage($entity_type_id)->loadRevision($latest_revision_id);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevisionId($entity_type_id, $entity_id) {
    if ($storage = $this->entityTypeManager->getStorage($entity_type_id)) {
      $revision_ids = $storage->getQuery()
        ->allRevisions()
        ->condition($this->entityTypeManager->getDefinition($entity_type_id)->getKey('id'), $entity_id)
        ->sort($this->entityTypeManager->getDefinition($entity_type_id)->getKey('revision'), 'DESC')
        ->range(0, 1)
        ->execute();
      if ($revision_ids) {
        return array_keys($revision_ids)[0];
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRevisionId($entity_type_id, $entity_id) {
    if ($storage = $this->entityTypeManager->getStorage($entity_type_id)) {
      $revision_ids = $storage->getQuery()
        ->condition($this->entityTypeManager->getDefinition($entity_type_id)->getKey('id'), $entity_id)
        ->sort($this->entityTypeManager->getDefinition($entity_type_id)->getKey('revision'), 'DESC')
        ->range(0, 1)
        ->execute();
      if ($revision_ids) {
        return array_keys($revision_ids)[0];
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function isLatestRevision(EntityInterface $entity) {
    if ($entity instanceof Node) {
      return $entity->getRevisionId() == $this->getLatestRevisionId($entity->getEntityTypeId(), $entity->id());
    }

  }

  /**
   * {@inheritdoc}
   */
  public function hasForwardRevision(EntityInterface $entity) {
    if ($entity instanceof Node) {
      return (!($this->getLatestRevisionId($entity->getEntityTypeId(), $entity->id()) == $this->getDefaultRevisionId($entity->getEntityTypeId(), $entity->id())));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(EntityInterface $entity) {
    $field_name = [];
    foreach (_workflow_info_fields($entity) as $field_info) {
      $field_name[] = $field_info->getName();
    }
    return $field_name[0];

  }

  /**
   * {@inheritdoc}
   */
  public function isPublishedState($workflowState) {
    $getWorkflowStateDetails = WorkflowState::load($workflowState);
    if (!empty($getWorkflowStateDetails)) {
      if ($getWorkflowStateDetails->node_status) {
        return TRUE;
      }
    }
    return FALSE;

  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevision($workflowState) {
    $getWorkflowStateDetails = WorkflowState::load($workflowState);
    if (isset($getWorkflowStateDetails->node_default_revision) && $getWorkflowStateDetails->node_default_revision) {
      return TRUE;
    }
    return FALSE;

  }

  /**
   * {@inheritdoc}
   */
  public function getWorkFlowId(EntityInterface $entity) {
    $workflow = workflow_get_workflows_by_type($entity->bundle(), $entity->getEntityTypeId());
    if (!empty($workflow)) {
      return $workflow->id();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousStateId(EntityInterface $entity) {
     $workflowHistroy = \Drupal::database()->select('workflow_transition_history', 'wth')->fields('wth', ['to_sid'])->condition('entity_id', $entity->id(), '=')->orderBy('revision_id', 'DESC')->range(0, 1)->execute()->fetchField();
     return $workflowHistroy;
  }

  /**
   * {@inheritdoc}
   */
  public function isModerateEntity(EntityInterface $entity) {
    $isModerate = $this->getWorkFlowId($entity);
    if (!empty($isModerate)) {
      return TRUE;
    }
    return FAlSE;
    
  }

  /**
   * {@inheritdoc}
   */
  public function loadTemplate($entity_id) {
    $entity = entity_load('node', $entity_id);

  }

}
