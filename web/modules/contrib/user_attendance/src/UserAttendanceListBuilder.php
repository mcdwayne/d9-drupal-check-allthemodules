<?php

namespace Drupal\user_attendance;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of User attendance entities.
 *
 * @ingroup user_attendance
 */
class UserAttendanceListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('User attendance ID');
    $header['user'] = $this->t('Name');
    $header['start'] = $this->t('Start');
    $header['end'] = $this->t('End');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\user_attendance\Entity\UserAttendance */
    $row['id'] = $entity->id();

    $user = $entity->getOwner();
    $row['user'] = $this->l(
      $entity->getOwner()->label(),
      new Url(
        'entity.user.canonical', array(
          'user' => $user->id(),
        )
      )
    );

    $start_time = $entity->getStartTime();
    if(!empty($start_time)) {
      $start_time = \Drupal::service('date.formatter')->format($start_time);
    }
    $row['start'] = $start_time;

    $end_time = $entity->getEndTime();
    if(!empty($end_time)) {
      $end_time = \Drupal::service('date.formatter')->format($end_time);
    }
    $row['end'] = $end_time;

    return $row + parent::buildRow($entity);
  }

}
