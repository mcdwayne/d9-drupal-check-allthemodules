<?php

namespace Drupal\timetable_cron;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class TimetableCronListBuilder.
 *
 * Form class for listing timetable_cron config entities.
 */
class TimetableCronListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Function');
    $header['status'] = $this->t('Status');
    $header['minute'] = $this->t('Minute');
    $header['hour'] = $this->t('Hour');
    $header['day'] = $this->t('Day');
    $header['month'] = $this->t('Month');
    $header['weekday'] = $this->t('Weekday');
    $header['desc'] = $this->t('Description');
    $header['lastrun'] = $this->t('Last run');
    $header['force'] = $this->t('Force onced');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    $row['id'] = $entity->id;
    $row['status'] = ($entity->status == 1 ? $this->t('On') : $this->t('Off'));
    $row['minute'] = $entity->minute;
    $row['hour'] = $entity->hour;
    $row['day'] = $entity->day;
    $row['month'] = $entity->month;
    $row['weekday'] = $entity->weekday;
    $row['desc'] = $entity->desc;
    $row['lastrun'] = ($entity->lastrun != '' ? date('d.m.Y, H:i:s', $entity->lastrun) : '');
    $row['force'] = ($entity->force == 1 ? $this->t('Yes') : $this->t('No'));

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    $build = parent::render();

    $build['#empty'] = $this->t('There are no cron items available.');
    return $build;
  }

}
