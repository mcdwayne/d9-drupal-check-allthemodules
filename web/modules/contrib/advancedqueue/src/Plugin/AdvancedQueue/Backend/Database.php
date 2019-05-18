<?php

namespace Drupal\advancedqueue\Plugin\AdvancedQueue\Backend;

use Drupal\advancedqueue\Job;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the database queue backend.
 *
 * @AdvancedQueueBackend(
 *   id = "database",
 *   label = @Translation("Database"),
 * )
 */
class Database extends BackendBase implements SupportsDeletingJobsInterface, SupportsListingJobsInterface, SupportsReleasingJobsInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new Database object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TimeInterface $time, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $time);

    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('datetime.time'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() {
    // No need to do anything, all database queues share the same table.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() {
    // Delete all jobs in the current queue.
    $this->connection->delete('advancedqueue')
      ->condition('queue_id', $this->queueId)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupQueue() {
    // Reset expired jobs.
    $this->connection->update('advancedqueue')
      ->fields([
        'state' => Job::STATE_QUEUED,
        'expires' => 0,
      ])
      ->condition('expires', 0, '<>')
      ->condition('expires', $this->time->getCurrentTime(), '<')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function countJobs() {
    // Ensure each state gets a count, even if it's 0.
    $jobs = [
      Job::STATE_QUEUED => 0,
      Job::STATE_PROCESSING => 0,
      Job::STATE_SUCCESS => 0,
      Job::STATE_FAILURE => 0,
    ];
    $query = 'SELECT state, COUNT(job_id) FROM {advancedqueue} WHERE queue_id = :queue_id GROUP BY state';
    $counts = $this->connection->query($query, [':queue_id' => $this->queueId])->fetchAllKeyed();
    foreach ($counts as $state => $count) {
      $jobs[$state] = $count;
    }

    return $jobs;
  }

  /**
   * {@inheritdoc}
   */
  public function enqueueJob(Job $job, $delay = 0) {
    $this->enqueueJobs([$job], $delay);
  }

  /**
   * {@inheritdoc}
   */
  public function enqueueJobs(array $jobs, $delay = 0) {
    if (count($jobs) > 1) {
      // Make the inserts atomic, and improve performance on certain engines.
      $transaction = $this->connection->startTransaction();
    }

    /** @var \Drupal\advancedqueue\Job $job */
    foreach ($jobs as $job) {
      $job->setQueueId($this->queueId);
      $job->setState(Job::STATE_QUEUED);
      $job->setAvailableTime($this->time->getCurrentTime() + $delay);

      $fields = $job->toArray();
      unset($fields['id']);
      $fields['payload'] = json_encode($fields['payload']);
      // InsertQuery supports inserting multiple rows at once, which is faster,
      // but that doesn't give us the inserted job IDs.
      $query = $this->connection->insert('advancedqueue')->fields($fields);
      $job_id = $query->execute();

      $job->setId($job_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retryJob(Job $job, $delay = 0) {
    if ($job->getState() != Job::STATE_FAILURE) {
      throw new \InvalidArgumentException('Only failed jobs can be retried.');
    }

    $job->setNumRetries($job->getNumRetries() + 1);
    $job->setAvailableTime($this->time->getCurrentTime() + $delay);
    $job->setState(Job::STATE_QUEUED);
    $this->updateJob($job);
  }

  /**
   * {@inheritdoc}
   */
  public function claimJob() {
    // Claim a job by updating its expire fields. If the claim is not successful
    // another thread may have claimed the job in the meantime. Therefore loop
    // until a job is successfully claimed or we are reasonably sure there
    // are no unclaimed jobs left.
    while (TRUE) {
      $query = 'SELECT * FROM {advancedqueue}
        WHERE queue_id = :queue_id AND state = :state AND available <= :now AND expires = 0 
        ORDER BY available, job_id ASC';
      $params = [
        ':queue_id' => $this->queueId,
        ':state' => Job::STATE_QUEUED,
        ':now' => $this->time->getCurrentTime(),
      ];
      $job_definition = $this->connection->queryRange($query, 0, 1, $params)->fetchAssoc();
      if (!$job_definition) {
        // No jobs left to claim.
        return NULL;
      }

      // Try to update the item. Only one thread can succeed in updating the
      // same row. We cannot rely on the request time because items might be
      // claimed by a single consumer which runs longer than 1 second. If we
      // continue to use request time instead of current time, we steal
      // time from the lease, and will tend to reset items before the lease
      // should really expire.
      $state = Job::STATE_PROCESSING;
      $expires = $this->time->getCurrentTime() + $this->configuration['lease_time'];
      $update = $this->connection->update('advancedqueue')
        ->fields([
          'state' => $state,
          'expires' => $expires,
        ])
        ->condition('job_id', $job_definition['job_id'])
        ->condition('expires', 0);
      // If there are affected rows, the claim succeeded.
      if ($update->execute()) {
        $job_definition['id'] = $job_definition['job_id'];
        unset($job_definition['job_id']);
        $job_definition['payload'] = json_decode($job_definition['payload'], TRUE);
        $job_definition['state'] = $state;
        $job_definition['expires'] = $expires;
        return new Job($job_definition);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onSuccess(Job $job) {
    $job->setProcessedTime($this->time->getCurrentTime());
    $this->updateJob($job);
  }

  /**
   * {@inheritdoc}
   */
  public function onFailure(Job $job) {
    $job->setProcessedTime($this->time->getCurrentTime());
    $this->updateJob($job);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseJob($job_id) {
    $this->connection->update('advancedqueue')
      ->fields([
        'state' => Job::STATE_QUEUED,
        'expires' => 0,
      ])
      ->condition('job_id', $job_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteJob($job_id) {
    $this->connection->delete('advancedqueue')
      ->condition('job_id', $job_id)
      ->execute();
  }

  /**
   * Updates the given job.
   *
   * @param \Drupal\advancedqueue\Job $job
   *   The job.
   */
  protected function updateJob(Job $job) {
    $this->connection->update('advancedqueue')
      ->fields([
        'payload' => json_encode($job->getPayload()),
        'state' => $job->getState(),
        'message' => $job->getMessage(),
        'num_retries' => $job->getNumRetries(),
        'available' => $job->getAvailableTime(),
        'processed' => $job->getProcessedTime(),
        'expires' => $job->getExpiresTime(),
      ])
      ->condition('job_id', $job->getId())
      ->execute();
  }

}
