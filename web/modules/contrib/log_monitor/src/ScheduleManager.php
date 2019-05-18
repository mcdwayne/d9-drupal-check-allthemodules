<?php
/**
 * Created by PhpStorm.
 * User: gurwinder
 * Date: 10/26/17
 * Time: 2:00 PM
 */

namespace Drupal\log_monitor;

use Drupal\Core\Entity\EntityInterface;

class ScheduleManager {


  /**
   * Check which entities are ready to be processed.
   *
   * Note: Since the validation is driven by cron, we cannot process in intervals
   * with granularity less than that of cron. The frequency of the interval set
   * in the rule's scheduler must be equal to or higher than the frequency of this
   * cron job.
   */
  public function validate() {
    $entities = \Drupal::entityTypeManager()
      ->getStorage('log_monitor_rule')
      ->loadByProperties(['status' => '1']);
    foreach ($entities as $entity) {
      // Check if it's time to process this entity
      $next_run = \Drupal::state()->get('log_monitor.' . $entity->id());
      $now = new \DateTime('now');
      if (is_null($next_run)) {
        \Drupal::logger('log_monitor')
          ->error('Could not determine next run time; the entity ' . $entity->id() . ' was not saved correctly.');
      }
      else {
        if ($now >= $next_run) {
          $this->process($entity);
          $next_run = LogMonitorHelper::getNextRun($entity, $now);
          \Drupal::state()->set('log_monitor.' . $entity->id(), $next_run);
        }
      }
    }
  }

  /**
   * Adds the entity to a queue to be processed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function process(EntityInterface $entity) {
    $queue = \Drupal::queue('reaction_processor');
    $queue->createItem($entity);
  }

}
