<?php

namespace Drupal\transcoding\Event;

use Drupal\transcoding\TranscodingJobInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class TranscodingJobEvent
 * @package Drupal\transcoding\Event
 */
class TranscodingJobEvent extends Event {

  /**
   * The job.
   *
   * @var \Drupal\transcoding\TranscodingJobInterface
   */
  protected $job;

  /**
   * @inheritDoc
   */
  public function __construct(TranscodingJobInterface $job) {
    $this->job = $job;
  }

  /**
   * @return \Drupal\transcoding\TranscodingJobInterface
   */
  public function getJob() {
    return $this->job;
  }

}
