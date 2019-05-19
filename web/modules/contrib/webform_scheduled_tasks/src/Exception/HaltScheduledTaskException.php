<?php

namespace Drupal\webform_scheduled_tasks\Exception;

/**
 * An exception thrown which should halt the execution of the scheduled task.
 *
 * This may be used where circumstances are such that retrying the scheduled
 * task is unlikely to be successful.
 */
class HaltScheduledTaskException extends \Exception {
}
