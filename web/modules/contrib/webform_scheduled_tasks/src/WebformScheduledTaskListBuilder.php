<?php

namespace Drupal\webform_scheduled_tasks;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * A scheduled task list builder.
 */
class WebformScheduledTaskListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $associated_webform = \Drupal::routeMatch()->getParameter('webform');
    $query = $this->getStorage()->getQuery()
      ->condition('webform', $associated_webform, '=')
      ->sort($this->entityType->getKey('id'));
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => $this->t('Task name'),
      'type' => $this->t('Task type'),
      'result_set' => $this->t('Result set'),
      'status' => $this->t('Status'),
      'next_scheduled_run' => $this->t('Next scheduled run'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $entity */
    return [
      'label' => $entity->label(),
      'type' => $entity->getTaskPlugin()->getPluginDefinition()['label'],
      'result_set' => $entity->getResultSetPlugin()->getPluginDefinition()['label'],
      'status' => $entity->isHalted() ? $this->t('Halted') : $this->t('Active'),
      'next_scheduled_run' => $entity->getNextTaskRunDate() ? \Drupal::service('date.formatter')->format($entity->getNextTaskRunDate()) : $this->t('Unscheduled'),
    ] + parent::buildRow($entity);
  }

}
