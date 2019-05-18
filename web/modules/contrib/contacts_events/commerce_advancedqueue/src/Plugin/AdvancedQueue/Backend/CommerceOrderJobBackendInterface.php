<?php

namespace Drupal\commerce_advancedqueue\Plugin\AdvancedQueue\Backend;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\Plugin\AdvancedQueue\Backend\BackendInterface;

/**
 * An interface for queues that support CommerceOrderJob jobs.
 *
 * Implementations provides additional means to manage jobs for a particular
 * order and must protect against multiple jobs being processed concurrently for
 * the same order.
 */
interface CommerceOrderJobBackendInterface extends BackendInterface {

  /**
   * Gets an estimated number of jobs in the queue.
   *
   * The accuracy of this number might vary.
   * On a busy system with a large number of consumers and jobs, the result
   * might only be valid for a fraction of a second and not provide an
   * accurate representation.
   *
   * @param int $order_id
   *   The Order ID to filter the count for.
   *
   * @return array
   *   The estimated number of jobs, grouped per job status.
   *   Only the estimate for the 'queued' status is guaranteed to be present,
   *   other estimates (processing/success/failed) depend on backend
   *   capabilities and configuration.
   */
  public function countJobsForOrder($order_id);

  /**
   * Claims the next available job for processing.
   *
   * @param int $order_id
   *   The Order ID to filter the jobs for.
   *
   * @return \Drupal\commerce_advancedqueue\CommerceOrderJob|null
   *   The job, or NULL if none available.
   */
  public function claimJobForOrder($order_id);

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   *   Thrown if $job is not a CommerceOrderJob.
   */
  public function enqueueJob(Job $job, $delay = 0);

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   *   Thrown if any of $jobs is not a CommerceOrderJob.
   */
  public function enqueueJobs(array $jobs, $delay = 0);

}
