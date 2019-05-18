<?php

namespace Drupal\drd\Plugin\Action;

/**
 * Provides a 'Update' action.
 *
 * @Action(
 *  id = "drd_action_update",
 *  label = @Translation("Run update.php"),
 *  type = "drd_domain",
 * )
 */
class Update extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  protected function getFollowUpAction() {
    return 'drd_action_info';
  }

}
