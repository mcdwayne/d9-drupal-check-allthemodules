<?php

namespace Drupal\drd\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\drd\Plugin\Action\Base;

/**
 * Abstract class for AdvancedQueue JobType plugins.
 */
abstract class Action extends JobTypeBase implements ActionInterface {

  /**
   * Action plugin.
   *
   * @var \Drupal\drd\Plugin\Action\Base
   */
  protected $action;

  /**
   * Job parameters.
   *
   * @var array
   */
  protected $payload = [];

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    $this->payload = $job->getPayload();
    $this->action = Base::instance($this->payload['action']);
    if (!$this->action) {
      return new JobResult(Job::STATE_FAILURE, 'Action plugin not found.');
    }

    $this->action->setArguments(json_decode($this->payload['arguments'], TRUE));

    $result = $this->processAction();
    $this->payload['output'] = $this->action->getOutput();
    $job->setPayload($this->payload);
    return new JobResult($result ? Job::STATE_SUCCESS : Job::STATE_FAILURE);
  }

}
