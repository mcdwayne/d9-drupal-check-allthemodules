<?php

namespace Drupal\gitlab_time_tracker_migration;
use Drupal\gitlab_time_tracker\GitlabClientInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Class GitlabImportController.
 */
class GitlabImportController implements GitlabImportControllerInterface {

  /**
   * Drupal\gitlab_time_tracker_migration\GitlabClientInterface definition.
   *
   * @var \Drupal\gitlab_time_tracker_migration\GitlabClientInterface
   */
  protected $timeTrackerImportGitlab;

  /**
   * Drupal\gitlab_time_tracker_migration\GitlabClientInterface definition.
   *
   * @var Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;
  /**
   * Constructs a new GitlabImportController object.
   */
  public function __construct(GitlabClientInterface $gitlab_time_tracker_migration_gitlab, MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->timeTrackerImportGitlab = $gitlab_time_tracker_migration_gitlab;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  public function migrateProjects($project_id = NULL, $update = FALSE) {
    if (is_numeric($project_id)) {
      $migration = $this->migrationPluginManager->createInstance(
        'gitlab_time_tracker_node_project',
        [
          'source' => [
            'plugin' => 'gitlab_project',
            'track_changes' => TRUE,
            'project_id' => $project_id,
          ],
        ]
      );
    }
    else {
      $migration = $this->migrationPluginManager->createInstance(
        'gitlab_time_tracker_node_project',
        []
      );
    }

    if ($update) {
      $migration->getIdMap()->prepareUpdate();
    }

    $migration->setStatus(MigrationInterface::STATUS_IDLE);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  public function migrateUsers() {
    $migration = $this->migrationPluginManager->createInstance(
      'gitlab_time_tracker_user',
      []
    );

    $migration->setStatus(MigrationInterface::STATUS_IDLE);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  public function migrateIssues($project_id = 0, $update = FALSE) {
    $migration = $this->migrationPluginManager->createInstance(
      'gitlab_time_tracker_node_issue',
      [
        'source' => [
          'plugin' => 'gitlab_issue',
          'track_changes' => TRUE,
          'project_id' => $project_id,
        ],
      ]
    );

    if ($update) {
      $migration->getIdMap()->prepareUpdate();
    }

    $migration->setStatus(MigrationInterface::STATUS_IDLE);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }


  public function migrateTimeTrack($project_id = 0, $issue_id = 0, $update = FALSE) {
    $migration = $this->migrationPluginManager->createInstance(
      'gitlab_time_tracker_node_timetrack',
      [
        'source' => [
          'plugin' => 'gitlab_time_track',
          'track_changes' => TRUE,
          'project_id' => $project_id,
          'issue_id' => $issue_id,
        ],
      ]
    );
    $migration->setStatus(MigrationInterface::STATUS_IDLE);

    if ($update) {
      $migration->getIdMap()->prepareUpdate();
    }

    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }
}
