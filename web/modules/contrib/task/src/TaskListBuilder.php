<?php

namespace Drupal\task;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Task entities.
 *
 * @ingroup task
 */
class TaskListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Task ID');
    $header['name'] = $this->t('Name');
    $header['status'] = $this->t('Status');
    $header['expire'] = $this->t('Expiration Date');
    $header['close'] = $this->t('Close Date');
    $header['close_type'] = $this->t('Close Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\task\Entity\Task */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute($entity->label(), 'entity.task.canonical', ['task' => $entity->id()]);
    $row['status'] = $entity->getStatus();
    $expire = $entity->get('expire_date')->value;
    $row['expire'] = date('Y-m-d H:i:s', $expire);
    $close = $entity->get('close_date')->value;
    $row['close'] = date('Y-m-d H:i:s', $close);
    $row['close_type'] = $entity->get('close_type')->value;
    return $row + parent::buildRow($entity);
  }

}
