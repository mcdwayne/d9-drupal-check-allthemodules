<?php

namespace Drupal\recurly_aegir\HostingServiceCalls;

/**
 * Common functionality for all hosting service calls requiring task creation.
 */
trait TaskCreationTrait {

  /**
   * Fetches the name of the task type to be created.
   *
   * @return string
   *   The name of the task type.
   */
  public static function getTaskType() {
    return static::TASK_TYPE;
  }

  /**
   * Returns the Aegir task ID returned after creating a task.
   *
   * @return int
   *   The task ID.
   *
   * @throws TaskCreationFailedException
   *   If there is no task ID because the service call failed.
   */
  public function getTaskId() {
    if (empty($this->response['nid'])) {
      throw new TaskCreationFailedException(sprintf(
        'Task creation failed for %s via %s with template %s.',
        $this->getSiteName(),
        (new \ReflectionClass(get_class($this)))->getShortName(),
        property_exists($this, 'template') ? $this->template : 'NO TEMPLATE'
      ));
    }
    return $this->response['nid'];
  }

  /**
   * Returns the path of the remote object being acted upon.
   */
  protected function getRemoteTargetPath() {
    return '/hosting/task/' . $this->getRemoteTargetId();
  }

  /**
   * Returns the ID of the remote object being acted upon.
   */
  protected function getRemoteTargetId() {
    return $this->getTaskId();
  }

}
