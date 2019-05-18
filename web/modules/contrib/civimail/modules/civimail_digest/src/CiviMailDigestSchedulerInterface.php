<?php

namespace Drupal\civimail_digest;

/**
 * Interface CiviMailDigestSchedulerInterface.
 */
interface CiviMailDigestSchedulerInterface {

  /**
   * Notify validators after preparation.
   */
  const SCHEDULER_NOTIFY = 'prepare_notify';

  /**
   * Send to groups after preparation.
   */
  const SCHEDULER_SEND = 'prepare_send';

  /**
   * Checks if the digest scheduler is configured as active.
   *
   * @return bool
   *   The scheduler configuration status.
   */
  public function isSchedulerActive();

  /**
   * Checks if the digest can be prepared.
   *
   * Implies the following conditions:
   * - the scheduler is active
   * - the digest has enough content
   * - configured digest time is greater or equal than the current time.
   *
   * @return bool
   *   The scheduler configuration status.
   */
  public function canPrepareDigest();

  /**
   * Executes the configured operation for the scheduler.
   *
   * Prepare the digest then send the digest to groups
   * or send a notification to validators
   * depending on the configuration.
   * Pre-condition: canPrepareDigest() evaluated to TRUE.
   *
   * @return bool
   *   The status of the operation.
   */
  public function executeSchedulerOperation();

  /**
   * Notifies the validator groups if a new digest is ready.
   *
   * @return bool
   *   Status of the notification.
   */
  public function notifyValidators($digest_id);

}
