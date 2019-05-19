<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Controller\ScheduleItemController.
 */

namespace Drupal\station_schedule\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\station_schedule\ScheduleInterface;

/**
 * @todo.
 */
class ScheduleItemController extends ControllerBase {

  public function addScheduleItem(ScheduleInterface $station_schedule, $start, $finish) {
    $entity = $this->entityTypeManager()->getStorage('station_schedule_item')->create([
      'schedule' => $station_schedule->id(),
      'start' => $start,
      'finish' => $finish,
    ]);

    return $this->entityFormBuilder()->getForm($entity, 'add');
  }

}
