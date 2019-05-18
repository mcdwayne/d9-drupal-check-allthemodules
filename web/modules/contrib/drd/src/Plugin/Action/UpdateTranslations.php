<?php

namespace Drupal\drd\Plugin\Action;

/**
 * Provides a 'UpdateTranslations' action.
 *
 * @Action(
 *  id = "drd_action_update_translations",
 *  label = @Translation("Update Translations"),
 *  type = "drd_domain",
 * )
 */
class UpdateTranslations extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  protected function getFollowUpAction() {
    return 'drd_action_info';
  }

}
