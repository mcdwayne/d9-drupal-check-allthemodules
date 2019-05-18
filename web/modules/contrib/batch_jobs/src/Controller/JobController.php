<?php

namespace Drupal\batch_jobs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Timer;
use Drupal\batch_jobs\Job;

/**
 * Job callbacks.
 */
class JobController extends ControllerBase {

  /**
   * Batch job title.
   *
   * @param int $bid
   *   Batch ID.
   * @param string $token
   *   User batch token.
   *
   * @return string
   *   Job title.
   */
  public function title($bid = NULL, $token = NULL) {
    $job = new Job($bid);
    if (!$job->access($token)) {
      return '';
    }
    return $job->title;
  }

  /**
   * Run a batch job.
   *
   * @param int $bid
   *   Batch ID.
   * @param string $token
   *   User batch token.
   *
   * @return array
   *   Render array containing the progress bar.
   */
  public function run($bid = NULL, $token = NULL) {
    $job = new Job($bid);
    if (!$job->access($token)) {
      return [
        '#type' => 'markup',
        '#markup' => '',
      ];
    }

    $content = '<div class="batch batch-' . $job->bid . ' batch-' . $token .
     '" id="progress"></div>';
    $complete = '<div class="batch-progress"></div>';
    $complete .= '<div class="batch-complete"></div>';
    $complete .= '<div class="batch-jobs"></div>';
    $complete = '<div class="batch-status">' . $complete . '</div>';
    $content .= $complete;
    $build['progressbar'] = [
      '#type' => 'markup',
      '#markup' => $content,
    ];
    $build['progressbar']['#attached']['library'][] = 'batch_jobs/batch_jobs';

    return $build;
  }

  /**
   * Run a set of tasks.
   *
   * @param int $bid
   *   Batch ID.
   * @param string $token
   *   User batch token.
   *
   * @return Json
   *   Status of the batch job.
   */
  public function callback($bid = NULL, $token = NULL) {
    $result = new \StdClass();
    $job = new Job($bid);
    if (!$job->access($token)) {
      $result->status = FALSE;
      return new JsonResponse($result);
    }

    $content = '';
    $job_data = $job->getData();
    Timer::start('batch_jobs_get_tasks_' . $job->bid);
    do {
      $tasks = batch_jobs_get_tasks($job->bid);
      if (count($tasks) == 0) {
        break;
      }
      foreach ($tasks as $task) {
        $task->startTask();
        // Array merge allows task parameters to override batch parameters.
        $task_params = array_merge($job_data, $task->getData());
        $message = [];
        $status = FALSE;
        foreach ($task->getCallbacks() as $callback) {
          $task_result = call_user_func($callback, $task_params);
          $status = $task_result->status;
          if (!isset($task_result->status) || !$task_result->status) {
            break;
          }
          if (isset($task_result->message)) {
            $message += $task_result->message;
          }
        }
        $task->endTask($status, $message);
      }
      $time = Timer::read('batch_jobs_get_tasks_' . $job->bid);
    } while ($time < 1000);
    Timer::stop('batch_jobs_get_tasks_' . $job->bid);
    $result->bid = $job->bid;
    $result->token = $token;
    $result->total = $job->total();
    $result->completed = $job->completed();
    $result->started = $job->started();
    $result->status = TRUE;
    return new JsonResponse($result);
  }

  /**
   * Run finish tasks.
   *
   * @param int $bid
   *   Batch ID.
   * @param string $token
   *   User batch token.
   *
   * @return Json
   *   Status of the finish tasks.
   */
  public function finish($bid = NULL, $token = NULL) {
    $result = new \StdClass();
    $job = new Job($bid);
    if (!$job->access($token)) {
      $result->status = FALSE;
      return new JsonResponse($result);
    }

    $result->status = $job->finish();

    return new JsonResponse($result);
  }

}
