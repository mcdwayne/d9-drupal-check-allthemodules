<?php
/**
 * Created by PhpStorm.
 * User: mike
 * Date: 10/6/17
 * Time: 4:03 PM
 */

namespace Drupal\log_monitor\Plugin\log_monitor\Scheduler;

use Drupal\Core\Form\FormStateInterface;

/**
 * Crontab log monitor scheduler..
 *
 * @LogMonitorScheduler(
 *   id = "crontab",
 *   title = @Translation("Crontab"),
 *   description = @Translation("Run actions on every cron run."),
 * )
 */
class Crontab extends SchedulerPluginBase {


  /**
   * Return the interval at which processing should happen.
   *
   * @return string
   */
  public function getInterval() {
    return 'PT0S';
  }

}
