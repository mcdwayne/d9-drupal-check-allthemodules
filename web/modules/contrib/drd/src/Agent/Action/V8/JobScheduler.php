<?php

namespace Drupal\drd\Agent\Action\V8;

/**
 * Provides a 'JobScheduler' code.
 */
class JobScheduler extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!\Drupal::moduleHandler()->moduleExists('job_scheduler')) {
      /* @noinspection PhpUndefinedFunctionInspection */
      job_scheduler_rebuild_all();
    }
    return [];
  }

}
