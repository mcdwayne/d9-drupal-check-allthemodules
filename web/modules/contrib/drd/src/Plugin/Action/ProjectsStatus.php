<?php

namespace Drupal\drd\Plugin\Action;

/**
 * Provides a 'ProjectStatus' action.
 *
 * @Action(
 *  id = "drd_action_projects_status",
 *  label = @Translation("Check status for all projects"),
 *  type = "drd",
 * )
 */
class ProjectsStatus extends BaseGlobal {

  /**
   * {@inheritdoc}
   */
  public function executeAction() {
    try {
      \Drupal::service('update.processor.drd')->fetchData();
    }
    catch (\Exception $ex) {
      return FALSE;
    }
    return TRUE;
  }

}
