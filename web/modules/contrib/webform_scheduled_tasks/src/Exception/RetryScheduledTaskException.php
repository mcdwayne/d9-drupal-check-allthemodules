<?php

namespace Drupal\webform_scheduled_tasks\Exception;

/**
 * An exception to throw is a tasks should continue to execute.
 *
 * This exception can be thrown when errors occur if the task should be queued
 * and retried after another interval has passed. This does not immediately or
 * on the next cron-run attempt the job again, it the task runner will wait
 * the configured duration before re-attempting.
 */
class RetryScheduledTaskException extends \Exception {
}
