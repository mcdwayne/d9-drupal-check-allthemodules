<?php

namespace Drupal\cloudconvert\Event;

use Drupal\cloudconvert\Entity\CloudConvertTaskInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Abstract Class CloudConvertFinishTask.
 *
 * @package Drupal\cloudconvert\Event
 */
abstract class CloudConvertBaseEvent extends Event {

  /**
   * The Cloud Convert Task.
   *
   * @var \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   */
  protected $cloudConvertTask;

  /**
   * The Cloud Convert Task Result.
   *
   * @var mixed
   */
  protected $result;

  /**
   * CloudConvertFinishTask constructor.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask
   *   Cloud Convert Task Entity.
   * @param mixed $result
   *   Result.
   */
  public function __construct(CloudConvertTaskInterface $cloudConvertTask, $result) {
    $this->cloudConvertTask = $cloudConvertTask;
    $this->result = $result;
  }

  /**
   * Get the Cloud Convert Task.
   *
   * @return \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   *   Cloud Convert Task Entity.
   */
  public function getCloudConvertTask() {
    return $this->cloudConvertTask;
  }

  /**
   * Get the Cloud Convert Task.
   *
   * @return \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   *   Cloud Convert Task Entity.
   */
  public function getResult() {
    return $this->result;
  }

}
