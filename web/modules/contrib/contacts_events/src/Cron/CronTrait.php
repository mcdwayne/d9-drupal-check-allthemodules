<?php

namespace Drupal\contacts_events\Cron;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Datetime\Time;
use Drupal\Core\State\StateInterface;

/**
 * A trait to help with controlling running of cron tasks.
 */
trait CronTrait {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * The run interval.
   *
   * One of:
   * - Y: Yearly.
   * - m: Monthly.
   * - d: Daily.
   * - H: Hourly.
   * - i: Minutely.
   *
   * @var string
   */
  protected $runInterval = 'd';

  /**
   * A formatted date/time to run after.
   *
   * @var string
   */
  protected $runAfterFormat = 'H:i:s';

  /**
   * The format for the run after date/time or NULL for no restriction.
   *
   * Should contain be in the format of self::$runAfterFormat.
   *
   * @var string|null
   */
  protected $runAfterTime = NULL;

  /**
   * Constructs the cron task.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\Time $time
   *   The time service.
   * @param mixed $services
   *   Additional services required. These are passed to static::init().
   *
   * @throws \Exception
   *   Thrown if the STATE_LAST_RUN constant is not set.
   */
  public function __construct(StateInterface $state, Time $time, ...$services) {
    if (!defined(static::class . '::STATE_LAST_RUN') || static::STATE_LAST_RUN === NULL) {
      throw new \Exception('The run time state key must be set.');
    }
    $this->state = $state;
    $this->time = $time;

    // Pass on additional services to the initialise.
    if (method_exists($this, 'initServices')) {
      $this->initServices(...$services);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invokeOnSchedule() {
    if (!$this->scheduledToRun()) {
      return;
    }

    try {
      $this->doInvoke();
      $this->setLastRunTime();
    }
    catch (\Exception $exception) {
      // Catch any errors.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invoke() {
    try {
      $this->doInvoke();
      $this->setLastRunTime();
    }
    catch (\Exception $exception) {
      // Catch any errors.
    }
  }

  /**
   * Run the actual task.
   */
  abstract protected function doInvoke();

  /**
   * {@inheritdoc}
   */
  public function scheduledToRun() {
    // Get our last run day.
    $last_run = $this->getLastRunTime();
    $now = $this->getCurrentTime();

    $format = 'Y-m-d H:i:s';
    $split_pos = strpos($format, $this->runInterval);
    if ($split_pos === FALSE) {
      throw new \Exception('Invalid run interval.');
    }
    $run_format = trim(substr($format, 0, $split_pos + 1), '- ');
    $after_format = trim(substr($format, $split_pos + 1), '- ');

    // If we have a last run, check it was before today.
    if ($last_run) {
      // If the last run was today (or future), we don't want to run.
      if ($last_run->format($run_format) >= $now->format($run_format)) {
        return FALSE;
      }
    }

    // If we have an $after, check that we are after that time.
    return !isset($this->runAfterTime) || ($now->format($after_format) >= $this->runAfterTime);
  }

  /**
   * Get the current time.
   *
   * @return \Drupal\Component\Datetime\DateTimePlus
   *   The current time.
   */
  protected function getCurrentTime() {
    return DateTimePlus::createFromTimestamp($this->time->getCurrentTime());
  }

  /**
   * Get the last run time.
   *
   * @return \Drupal\Component\Datetime\DateTimePlus|null
   *   The last run time or NULL if never.
   */
  protected function getLastRunTime() {
    $timestamp = $this->state->get(static::STATE_LAST_RUN);
    return isset($timestamp) ? DateTimePlus::createFromTimestamp($timestamp) : NULL;
  }

  /**
   * Set the last run time.
   */
  protected function setLastRunTime() {
    $this->state->set(static::STATE_LAST_RUN, $this->time->getCurrentTime());
  }

}
