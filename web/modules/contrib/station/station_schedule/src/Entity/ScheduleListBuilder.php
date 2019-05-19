<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Entity\ScheduleListBuilder.
 */

namespace Drupal\station_schedule\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * @todo.
 */
class ScheduleListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No schedules available. <a href=":link">Add schedule</a>.', [
      ':link' => Url::fromRoute('entity.station_schedule.add_form')->toString()
    ]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'title' => $this->t('Title'),
    ] + parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\station_schedule\ScheduleInterface $entity */
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $entity->toUrl(),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('update') && $entity->hasLinkTemplate('schedule')) {
      $operations['alter-schedule'] = array(
        'title' => $this->t('Alter schedule'),
        'weight' => 15,
        'url' => $entity->toUrl('schedule'),
      );
    }
    return $operations;
  }

}
