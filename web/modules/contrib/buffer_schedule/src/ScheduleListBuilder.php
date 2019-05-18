<?php

namespace Drupal\buffer_schedule;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Schedule entities.
 *
 * @ingroup buffer_schedule
 */
class ScheduleListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\buffer_schedule\Entity\Schedule */
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.schedule.edit_form',
      ['schedule' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
