<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Controller\AlterScheduleController.
 */

namespace Drupal\station_schedule\Controller;

use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Url;
use Drupal\station_schedule\DatetimeHelper;
use Drupal\station_schedule\ScheduleInterface;
use Drupal\station_schedule\ScheduleItemInterface;

/**
 * @todo.
 */
class AlterScheduleController {

  /**
   * @todo.
   *
   * @param \Drupal\station_schedule\ScheduleInterface $station_schedule
   *
   * @return array
   */
  public function alterSchedule(ScheduleInterface $station_schedule) {
    $header = [];
    $row = [];
    $minutes_per_day = 60 * 24;
    $day_names = DateHelper::weekDays();
    foreach ($station_schedule->getScheduledItemsByDay() as $day => $items) {
      $header[$day] = $day_names[$day];
      $row[$day] = ['data' => []];

      // The last finish pointer starts at the beginning of the day.
      $last_finish = $day * $minutes_per_day;
      $day_finish = ($day + 1) * $minutes_per_day;
      foreach ($items as $item) {
        $start = $item->start->value;
        // Display blocks for unscheduled time periods
        if ($last_finish != $start) {
          $row[$day]['data'][] = $this->buildUnscheduledItem($station_schedule, $last_finish, $start);

        }
        $last_finish = $item->finish->value;

        // Display the schedule item
        $row[$day]['data'][] = $this->buildScheduledItem($item);
      }
      // Display a block for any remaining time during the day.
      if ($last_finish < $day_finish) {
        $row[$day]['data'][] = $this->buildUnscheduledItem($station_schedule, $last_finish, $day_finish);
      }
    }

    // Render the table
    $output = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => ['data' => $row],
      '#attached' => ['library' => ['station_schedule/schedule']],
      '#attributes' => [
        'id' => 'station-sch',
        'class' => [
          'station-sch-admin',
        ],
      ],
    ];
    return $output;
  }

  /**
   * @todo.
   *
   * @param \Drupal\station_schedule\ScheduleInterface $schedule
   * @param int $start
   * @param int $finish
   *
   * @return array
   */
  protected function buildUnscheduledItem(ScheduleInterface $schedule, $start, $finish) {
    $height = ($finish - $start);
    $link = Url::fromRoute('entity.station_schedule_item.add_form', ['station_schedule' => $schedule->id(), 'start' => $start, 'finish' => $finish])->toString();
    $output = [
      '#type' => 'container',
      '#attributes' => ['class' => ['station-sch-box', 'station-sch-unscheduled']],
      'children' => [
        '#prefix' => "<a id='schedule-{$start}' href='{$link}' data-drupal-station-schedule-height='$height'>",
        '#suffix' => '</a>',
        'time' => [
          '#markup' => DatetimeHelper::hourRange($start, $finish),
          '#prefix' => '<span class="station-sch-time">',
          '#suffix' => '</span>',
        ],
        'title' => [
          '#markup' => t('<em>Unscheduled</em>'),
          '#prefix' => '<span class="station-sch-title">',
          '#suffix' => '</span>',
        ],
      ],
    ];
    return $output;
  }

  /**
   * @todo.
   *
   * @param \Drupal\station_schedule\ScheduleItemInterface $schedule_item
   *
   * @return array
   */
  protected function buildScheduledItem(ScheduleItemInterface $schedule_item) {
    $start = $schedule_item->getStart();
    $finish = $schedule_item->getFinish();
    $height = ($finish - $start);
    $link = $schedule_item->toUrl('edit-form')->toString();
    $output = [
      '#type' => 'container',
      '#attributes' => ['class' => ['station-sch-box', 'station-sch-scheduled']],
      'children' => [
        'time' => [
          '#markup' => DatetimeHelper::hourRange($start, $finish),
          '#prefix' => "<a id='schedule-{$start}' href='{$link}' data-drupal-station-schedule-height='$height'><span class='station-sch-time'>",
          '#suffix' => '</span>',
        ],
        'title' => [
          '#markup' => $schedule_item->getProgram()->label(),
          '#prefix' => '<span class="station-sch-title">',
          '#suffix' => '</span></a>',
        ],
      ],
    ];
    return $output;
  }

}
