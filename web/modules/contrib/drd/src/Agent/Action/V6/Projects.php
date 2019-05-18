<?php

namespace Drupal\drd\Agent\Action\V6;

/**
 * Provides a 'Projects' code.
 */
class Projects extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $projects = array();
    $result = db_query("SELECT name, type, status, info FROM {system}");
    while ($record = db_fetch_object($result)) {
      $record->info = unserialize($record->info);
      $projects[] = $record;
    }

    $projects[] = array(
      'name' => 'drupal',
      'type' => 'core',
      'status' => 1,
      'info' => array(
        'core' => '6.x',
        'version' => VERSION,
        'project' => 'drupal',
        'hidden' => FALSE,
      ),
    );

    // Integration with the Hacked module.
    if (module_exists('hacked')) {
      $this->checkHacked($projects);
    }

    return $projects;
  }

  /**
   * Verify each project if it got hacked.
   *
   * @param array $projects
   *   The list of projects.
   */
  private function checkHacked(array &$projects) {
    module_load_include('inc', 'hacked', 'includes/hacked_project');
    foreach ($projects as &$project) {
      $hacked = new hackedProject($project['name']);
      $project['hacked'] = array(
        'report' => $hacked->compute_report(),
      );
      $project['hacked']['status'] = ($project['hacked']['report']['status'] == HACKED_STATUS_HACKED);
    }
  }

}
