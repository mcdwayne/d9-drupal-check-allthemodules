<?php

namespace Drupal\drd\Command;

/**
 * Class JobScheduler.
 *
 * @package Drupal\drd
 */
class JobScheduler extends BaseDomain {

  /**
   * Construct the JobScheduler command.
   */
  public function __construct() {
    parent::__construct();
    $this->actionKey = 'drd_action_job_scheduler';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drd:jobscheduler')
      ->setDescription($this->trans('commands.drd.action.jobscheduler.description'));
  }

}
