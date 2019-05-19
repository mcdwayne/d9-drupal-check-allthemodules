<?php

namespace Drupal\timetable_cron;

use Drupal\Core\Cron;

/**
 * Overrides default cron.
 */
class TimetableCron extends Cron {

  /**
   * Invokes any cron handlers implementing hook_cron.
   */
  protected function invokeCronHandlers() {
    // Define starting time of cron run.
    $starttime = [
      'minute' => date('i', time()),
      'hour' => date('G', time()),
      'day' => date('j', time()),
      'month' => date('n', time()),
      'weekday' => date('N', time()),
    ];

    // Get config entitys.
    $query = \Drupal::entityQuery('timetable_cron');
    $ids = $query->execute();
    $entitys = [];
    foreach ($ids as $id) {
      $entity = \Drupal::entityTypeManager()->getStorage('timetable_cron')->load($id);
      $entitys[$entity->id()] = [
        'id' => $entity->id(),
        'status' => $entity->status,
        'force' => $entity->force,
        'minute' => $entity->minute,
        'hour' => $entity->hour,
        'day' => $entity->day,
        'month' => $entity->month,
        'weekday' => $entity->weekday,
        'desc' => $entity->desc,
        'lastrun' => $entity->lastrun,
        'delete' => FALSE,
      ];
    }

    // Get all cron handlers.
    foreach ($this->moduleHandler->getImplementations('cron') as $module) {
      // Save it if not exists.
      $function = $module . '_cron';
      if (!isset($entitys[$function])) {
        // Save default config.
        $new_entity = [
          'id' => $function,
          'status' => TRUE,
          'force' => FALSE,
          'minute' => '*',
          'hour' => '*',
          'day' => '*',
          'month' => '*',
          'weekday' => '*',
          'desc' => '',
          'lastrun' => '',
        ];
        \Drupal::entityTypeManager()->getStorage('timetable_cron')->create($new_entity)->save();
        $entitys = array_merge($entitys, [$function => $new_entity]);
      }
    }

    // #####################################################################
    // Description:
    // minute > Minute (0-59) or */10 for 10min, */30 for 30min
    // hour > Hour (0-23)
    // day > Day (1-31)
    // month > Month (1-12)
    // weekday > Weekday (0-7, Sunday is 0)
    // Go trough settings and check times.
    foreach ($entitys as $id => $cron) {
      // Check times for running.
      $runnow = TRUE;
      $status = (isset($cron['status']) ? $cron['status'] : TRUE);
      $minute = (isset($cron['minute']) ? $cron['minute'] : '*');
      $hour = (isset($cron['hour']) ? $cron['hour'] : '*');
      $day = (isset($cron['day']) ? $cron['day'] : '*');
      $month = (isset($cron['month']) ? $cron['month'] : '*');
      $weekday = (isset($cron['weekday']) ? $cron['weekday'] : '*');
      $force = (isset($cron['force']) ? $cron['force'] : FALSE);
      $function = $cron['id'];

      // Check status.
      if ($status == 0) {
        continue;
      }

      // Check minute.
      if ($minute != $starttime['minute'] and $minute != '*') {
        // Check if isset as Intervall.
        if (strpos($minute, '/') !== FALSE) {
          // Minute is an Intervall.
          $minute = explode('/', $minute);
          if ($minute[0] == '*') {
            // Check if minute now possible with Intervall.
            if ($starttime['minute'] % $minute[1] != 0) {
              // Is not possible to calculate!
              $runnow = FALSE;
            }
          }
          else {
            $runnow = FALSE;
          }

        }
        else {
          $runnow = FALSE;
        }
      }

      // Check hour.
      if ($hour != $starttime['hour'] and $hour != '*') {
        // Check if isset as Intervall.
        if (strpos($hour, '/') !== FALSE) {
          // Hour is an Intervall.
          $hour = explode('/', $hour);
          if ($hour[0] == '*') {
            // Check if hour now possible with Intervall.
            if ($starttime['hour'] % $hour[1] != 0) {
              // Is not possible to calculate!
              $runnow = FALSE;
            }
          }
          else {
            $runnow = FALSE;
          }

        }
        else {
          $runnow = FALSE;
        }
      }

      // Check day.
      if ($day != $starttime['day'] and $day != '*') {
        $runnow = FALSE;
      }

      // Check month.
      if ($month != $starttime['month'] and $month != '*') {
        $runnow = FALSE;
      }

      // Check weekday.
      if ($weekday != $starttime['weekday'] and $weekday != '*') {
        $runnow = FALSE;
      }

      // Check force.
      if ($force == TRUE) {
        $runnow = TRUE;
      }

      if ($runnow) {
        // Set runtime.
        $lastruntime = time();
        $entitys[$id]['lastrun'] = $lastruntime;

        // Reset force.
        $entitys[$id]['force'] = FALSE;

        // Run cronjob.
        $this->logger->info('Run @function on special time: @cron', ['@function' => $function, '@cron' => json_encode($cron)]);

        // Do not let an exception thrown by one module disturb another.
        if (function_exists($function)) {
          try {
            call_user_func_array($function, []);
          }
          catch (\Exception $e) {
            watchdog_exception('cron', $e);
          }
        }
        else {
          // Function not exists.
          $this->logger->error('Not possible to call function @function on special time: @cron', ['@function' => $function, '@cron' => json_encode($cron)]);

          // Delete cid because they don't exists.
          $entitys[$id]['delete'] = TRUE;
        }

      }
    }

    // Update entitys.
    foreach ($entitys as $entity) {
      $saved_entity = \Drupal::entityTypeManager()->getStorage('timetable_cron')->load($entity['id']);

      // Check delete.
      if (isset($entity['delete']) && $entity['delete'] == TRUE) {
        $saved_entity->delete();
      }
      else {
        // Save entity.
        $saved_entity->force = $entity['force'];
        $saved_entity->lastrun = $entity['lastrun'];
        $saved_entity->save();
      }
    }

  }

}
