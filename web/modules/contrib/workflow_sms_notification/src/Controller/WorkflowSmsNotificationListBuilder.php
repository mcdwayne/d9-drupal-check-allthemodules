<?php

namespace Drupal\workflow_sms_notification\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Url;
use Drupal\workflow\Entity\WorkflowState;

/**
 * Defines a class to build a listing of Workflow SMS notification entities.
 *
 * @see \Drupal\workflow_sms_notification\Entity\WorkflowNotification
 */
class WorkflowSmsNotificationListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['state_label'] = $this->t('State label');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $state_id = $entity->getState();
    $state = WorkflowState::loadMultiple([$state_id], $entity->getWorkflowId());
    if (isset($state[$entity->getState()])) {
      $label = $state[$entity->getState()]->label;
    }
    $row['state_label'] = isset($label) ? $label : '';
    $row['id'] = $entity->id();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = [];
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => Url::fromRoute('entity.workflow_sms_notification.edit_form', [
          'workflow_type' => $entity->getWorkflowId(),
          'workflow_sms_notification' => $entity->id(),
        ]),
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => Url::fromRoute('entity.workflow_sms_notification.delete_form', [
          'workflow_type' => $entity->getWorkflowId(),
          'workflow_sms_notification' => $entity->id(),
        ]),
      ];
    }

    return $operations;
  }

}
