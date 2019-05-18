<?php

namespace Drupal\gitlab_time_tracker_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * User source from gitlab database.
 *
 * @MigrateSource(
 *   id = "gitlab_time_track",
 *   source_module = "gitlab_time_tracker"
 * )
 */
class GitlabTimetrackSource extends SourcePluginBase {
  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $results = \Drupal::service('gitlab_time_tracker.gitlab')->fetchComments(
      $this->configuration['project_id'],
      $this->configuration['issue_id']
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
      'id' => 'Gitlab name',
      'email' => 'Email',
      'body' => 'Body',
    ];
  }

  public function __toString() {
    // This could cause a problem when using a lot of urls, may need to hash.
    return implode(',', $this->fields());
  }

}
