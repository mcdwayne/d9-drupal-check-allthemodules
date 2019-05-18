<?php

namespace Drupal\cron_service;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Collects cron services, manages their schedule an executes them on time.
 */
class CronServiceManager implements CronServiceManagerInterface {

  use StringTranslationTrait;

  /**
   * Injected state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Injected logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;

  /**
   * Services list.
   *
   * @var \Drupal\cron_service\CronServiceInterface[]
   */
  protected $handlers = [];

  /**
   * CronServiceManager constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal State.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Drupal logger.
   */
  public function __construct(StateInterface $state, LoggerChannelInterface $logger) {
    $this->state = $state;
    $this->log = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function addHandler(CronServiceInterface $instance, string $id) {
    $this->handlers[$id] = $instance;
  }

  /**
   * Executes all the handlers.
   */
  public function execute() {
    foreach (array_keys($this->handlers) as $id) {
      $this->executeHandler($id, FALSE);
    }
  }

  /**
   * Stores the value in a persistent storage.
   *
   * @param string $id
   *   Service id.
   * @param string $name
   *   Value name.
   * @param mixed $value
   *   Value to store.
   */
  protected function storeValue(string $id, string $name, $value) {
    $state_name = sprintf('cron_service.cron.%s.%s', $id, $name);
    $this->state->set($state_name, $value);
  }

  /**
   * Retrieves a value from a persistent storage.
   *
   * @param string $id
   *   Service id.
   * @param string $name
   *   Value name.
   * @param mixed $default
   *   Default value to return.
   *
   * @return mixed
   *   Value from the storage.
   */
  protected function getValue(string $id, string $name, $default = NULL) {
    $state_name = sprintf('cron_service.cron.%s.%s', $id, $name);
    return $this->state->get($state_name, $default);
  }

  /**
   * Returns next execution time.
   *
   * @param string $id
   *   Handler Id.
   *
   * @return int
   *   Unix timestamp.
   */
  public function getScheduledCronRunTime(string $id): int {
    return $this->handlers[$id] instanceof ScheduledCronServiceInterface ? (int) $this->getValue($id, 'schedule', 0) : 0;
  }

  /**
   * Returns true if the service can be executed.
   *
   * @param string $id
   *   Handler id.
   *
   * @return bool
   *   TRUE if it's time to run.
   */
  public function shouldRunNow(string $id): bool {
    if ($this->isForced($id)) {
      return TRUE;
    }
    $result = $this->getScheduledCronRunTime($id) <= time();
    if ($this->handlers[$id] instanceof TimeControllingCronServiceInterface) {
      $result = $result && $this->handlers[$id]->shouldRunNow();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function executeHandler(string $id, $force = FALSE): bool {
    if (isset($this->handlers[$id])) {
      if ($force || $this->shouldRunNow($id)) {
        $this->log->info($this->t('Start executing @id', ['@id' => $id]));
        $this->handlers[$id]->execute();
        $this->scheduleNextRunTime($id);
        $this->resetForceNextExecution($id);
        $this->log->debug($this->t('@id finished executing', ['@id' => $id]));
        return TRUE;
      }
      else {
        $this->log->debug($this->t('Skip execution of @id until @date', [
          '@id' => $id,
          '@date' => date('c', $this->getScheduledCronRunTime($id)),
        ]));
        return FALSE;
      }
    }
    else {
      $this->log->warning($this->t('Attempted to execute non existing cron handler with id @id', ['@id' => $id]));
      return FALSE;
    }
  }

  /**
   * Updates next run time in state.
   *
   * @param string $id
   *   Handler Id.
   */
  protected function scheduleNextRunTime(string $id) {
    if ($this->handlers[$id] instanceof ScheduledCronServiceInterface) {
      $next = $this->handlers[$id]->getNextExecutionTime();
      $this->storeValue($id, 'schedule', $next);
      // For unknown reason cache invalidation doesn't work on calling set()
      // which causes shouldRunNow() return the wrong value for some time.
      $this->state->resetCache();
      $this->log->debug($this->t('Next run is set to @date server time', ['@date' => date('r', $next)]));
    }
  }

  /**
   * Sets to force next execution of the service.
   *
   * It doesn't immediately executes the service but it forces to bypass all the
   * schedule checks on the next run.
   *
   * @param string $id
   *   Service id.
   */
  public function forceNextExecution(string $id) {
    $this->storeValue($id, 'forced', TRUE);
  }

  /**
   * Check whether the service execution was forced or not.
   *
   * @param string $id
   *   Service id.
   *
   * @return bool
   *   TRUE if the service execution was forced.
   */
  protected function isForced(string $id): bool {
    return (bool) $this->getValue($id, 'forced', FALSE);
  }

  /**
   * Resets force flag for the service.
   *
   * @param string $id
   *   Service id.
   */
  protected function resetForceNextExecution(string $id) {
    $this->storeValue($id, 'forced', FALSE);
  }

}
