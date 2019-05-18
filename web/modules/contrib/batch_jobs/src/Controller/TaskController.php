<?php

namespace Drupal\batch_jobs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\batch_jobs\Job;
use Drupal\batch_jobs\Task;

/**
 * Task callbacks.
 */
class TaskController extends ControllerBase {

  /**
   * Run a task.
   *
   * @param int $tid
   *   Task ID.
   * @param string $token
   *   Token string.
   *
   * @return Json
   *   Task status.
   */
  public function run($tid = NULL, $token = NULL) {
    $response = new \stdClass();
    $task = new Task($tid);
    if (!$task->access($token)) {
      $response->status = FALSE;
      return new JsonResponse($response);
    }
    $job = new Job($task->bid);
    if (!$job->access()) {
      $response->status = FALSE;
      return new JsonResponse($response);
    }
    if ($task->status) {
      // Task has already run.
      $response->status = FALSE;
      return new JsonResponse($response);
    }

    $task->startTask();

    // Array merge allows task parameters to override batch parameters.
    $params = array_merge($job->getData(), $task->getData());
    $message = [];
    foreach ($task->getCallbacks() as $callback) {
      $result = call_user_func($callback, $params);
      if (!isset($result->status) || !$result->status) {
        break;
      }
      if (isset($result->message)) {
        $message += $result->message;
      }
    }
    if (isset($result->status)) {
      $task->endTask($result->status, $message);
    }
    $response->tid = $task->tid;
    $response->title = $task->title;
    $response->start = $task->getStartString();
    $response->end = $task->getEndString();
    if ($result->status) {
      $response->status = '<div class="successful">' . t('Successful') .
        '</div>';
    }
    else {
      $response->status = '<div class="error">' . t('Error') . '</div>';
    }
    $response->message = print_r($message, TRUE);
    return new JsonResponse($response);
  }

}
