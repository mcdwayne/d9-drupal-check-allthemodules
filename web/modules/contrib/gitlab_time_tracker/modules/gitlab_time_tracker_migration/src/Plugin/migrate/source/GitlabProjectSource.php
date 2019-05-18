<?php

namespace Drupal\gitlab_time_tracker_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * User source from gitlab database.
 *
 * @MigrateSource(
 *   id = "gitlab_project",
 *   source_module = "gitlab_time_tracker"
 * )
 */
class GitlabProjectSource extends SourcePluginBase {
  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    if (isset($this->configuration['project_id'])) {
      $results = [\Drupal::service('gitlab_time_tracker.gitlab')->fetchProjects(
        $this->configuration['project_id']
      )];
    }
    else {
      $results = \Drupal::service('gitlab_time_tracker.gitlab')->fetchProjects();
    }

    return new \ArrayIterator($results);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => 'Name of user',
      'id' => 'Gitlab id',
      'creator_id' => 'Creator id',
      'description' => 'description',
      'name_with_namespace' => 'Name with namespace',
    ];
  }

  public function __toString() {
    // This could cause a problem when using a lot of urls, may need to hash.
    return implode(',', $this->fields());
  }

}
