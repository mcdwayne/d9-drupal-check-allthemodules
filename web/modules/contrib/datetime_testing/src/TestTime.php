<?php

namespace Drupal\datetime_testing;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\StateInterface;

/**
 * {@inheritdoc}
 */
class TestTime implements TestTimeInterface {

  /**
   * The normal time service being decorated.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $realTime;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Construct a TestTime object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $real_time
   *   A real (non-test) time object.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(TimeInterface $real_time, StateInterface $state) {
    $this->realTime = $real_time;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function setTime($time) {
    if (!is_string($time) && !is_int($time) && !is_float($time)) {
      throw new \Exception('Time to be set must be passed as a string, integer or float.');
    }
    if (is_string($time)) {
      $settings = ['current_time' => $this->getCurrentTime()];
      $timeObject = new TestDateTime($time, NULL, $settings);
      $time = $timeObject->getTimestamp();
    }

    $this->state->set('datetime_testing.specified_time', $time);
    if ($this->state->get('datetime_testing.time_passing') !== FALSE) {
      $this->state
        ->set('datetime_testing.time_started', $this->realTime->getCurrentMicroTime());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetTime() {
    $this->state->set('datetime_testing.specified_time', NULL);
    $this->state->set('datetime_testing.time_started', NULL);
    $this->state->set('datetime_testing.time_passing', NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function freezeTime() {
    // Do nothing if time already frozen.
    if ($this->state->get('datetime_testing.time_passing') !== FALSE) {
      $this->setTime($this->getCurrentMicroTime());
      $this->state->set('datetime_testing.time_passing', FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unfreezeTime() {
    // Do nothing if time not frozen.
    // If time_passing is null, don't set a start time.
    if ($this->state->get('datetime_testing.time_passing') === FALSE) {
      $this->state->set('datetime_testing.time_passing', TRUE);
      $this->state->set('datetime_testing.time_started', $this->realTime->getCurrentMicroTime());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentMicroTime() {
    $specifiedTime = $this->state->get('datetime_testing.specified_time');
    $baseTime = !empty($specifiedTime) ? $specifiedTime : $this->realTime->getCurrentMicroTime();
    $passed = $this->getMicroTimePassed();
    $timeNow = $baseTime + $passed;
    return $timeNow;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTime() {
    return (int) $this->getCurrentMicroTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestMicroTime() {
    $requestLag = $this->realTime->getCurrentMicroTime() - $this->realTime->getRequestMicroTime();
    return $this->getCurrentMicroTime() - $requestLag;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    return (int) $this->getRequestMicroTime();
  }

  /**
   * Get how much time has passed since it began to be allowed to flow freely.
   *
   * @return float
   *   How many seconds have passed.
   */
  protected function getMicroTimePassed() {
    $start = $this->state->get('datetime_testing.time_started');
    if (($this->state->get('datetime_testing.time_passing') !== FALSE) && !empty($start)) {
      $now = $this->realTime->getCurrentMicroTime();
      return $now - $start;
    }
    return 0;
  }

}
