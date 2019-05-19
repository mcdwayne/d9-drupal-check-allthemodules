<?php

namespace Drupal\webform_scheduled_tasks\Plugin\HealthzCheck;

use Drupal\healthz\Plugin\HealthzCheckBase;
use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTask;

/**
 * Provides a check for halted scheduled tasks.
 *
 * @HealthzCheck(
 *   id = "webform_scheduled_tasks_halted",
 *   title = @Translation("Halted webform scheduled tasks"),
 *   description = @Translation("Check for any scheduled tasks that were halted.")
 * )
 */
class HaltedTasksCheck extends HealthzCheckBase {

  /**
   * {@inheritdoc}
   */
  public function check() {
    /** @var \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface[] $schedules */
    $schedules = WebformScheduledTask::loadMultiple();
    foreach ($schedules as $schedule) {
      if ($schedule->isHalted()) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
