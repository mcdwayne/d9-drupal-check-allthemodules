<?php

namespace Drupal\drd\Plugin\Action;

/**
 * Provides a 'Cron' action.
 *
 * @Action(
 *  id = "drd_action_cron",
 *  label = @Translation("Cron"),
 *  type = "drd_domain",
 * )
 */
class Cron extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  protected function getFollowUpAction() {
    return 'drd_action_info';
  }

}
