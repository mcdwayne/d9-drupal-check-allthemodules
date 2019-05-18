<?php

namespace Drupal\drd\Plugin\Action;

/**
 * Provides a 'JobScheduler' action.
 *
 * @Action(
 *  id = "drd_action_job_scheduler",
 *  label = @Translation("JobScheduler"),
 *  type = "drd_domain",
 * )
 */
class JobScheduler extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  protected function getFollowUpAction() {
    return 'drd_action_info';
  }

}
