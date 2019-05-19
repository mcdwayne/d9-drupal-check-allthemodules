<?php

namespace Drupal\workflow_task;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Workflow task entities.
 *
 * @ingroup workflow_task
 */
class WorkflowTaskListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Workflow task ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\workflow_task\Entity\WorkflowTask */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.workflow_task.edit_form',
      ['workflow_task' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
