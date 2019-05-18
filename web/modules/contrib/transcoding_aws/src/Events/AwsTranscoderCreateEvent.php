<?php

namespace Drupal\transcoding_aws\Events;

use Drupal\transcoding\TranscodingJobInterface;
use Symfony\Component\EventDispatcher\Event;

class AwsTranscoderCreateEvent extends Event {

  /**
   * The transcoding job.
   *
   * @var \Drupal\transcoding\TranscodingJobInterface
   */
  protected $job;

  /**
   * Create request arguments.
   * @var array
   */
  protected $args = [];

  /**
   * @inheritDoc
   */
  public function __construct(TranscodingJobInterface $job) {
    $this->job = $job;
  }

  /**
   * Getter for the job.
   *
   * @return \Drupal\transcoding\TranscodingJobInterface
   */
  public function getJob() {
    return $this->job;
  }

  /**
   * @param array $args
   */
  public function setArgs(array $args) {
    $this->args = $args;
  }

  /**
   * Get an arguments array suitable for a job request.
   */
  public function getArgs() {
    $data = $this->getJob()->getServiceData();
    return $this->args + [
      'PipelineId' => $data['pipeline'],
      'Input' => ['Key' => $data['input']],
    ];
  }

}
