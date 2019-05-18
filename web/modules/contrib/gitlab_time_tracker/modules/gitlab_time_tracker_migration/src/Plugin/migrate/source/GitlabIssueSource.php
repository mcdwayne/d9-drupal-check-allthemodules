<?php

namespace Drupal\gitlab_time_tracker_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * User source from gitlab database.
 *
 * @MigrateSource(
 *   id = "gitlab_issue",
 *   source_module = "time_tracker_import"
 * )
 */
class GitlabIssueSource extends SourcePluginBase {
  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $results = \Drupal::service('gitlab_time_tracker.gitlab')->fetchIssues(
      $this->configuration['project_id']
    );
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
      'title' => 'title',
      'id' => 'Gitlab id',
      'iid' => 'Gitlab iid',
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
