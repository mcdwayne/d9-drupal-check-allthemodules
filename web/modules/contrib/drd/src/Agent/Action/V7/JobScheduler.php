<?php

namespace Drupal\drd\Agent\Action\V7;

/**
 * Provides a 'JobScheduler' code.
 */
class JobScheduler extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (module_exists('job_scheduler')) {
      job_scheduler_rebuild_all();
    }
    return array();
  }

}
