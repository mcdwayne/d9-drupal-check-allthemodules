<?php

namespace Drupal\gitlab_time_tracker_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * User source from gitlab database.
 *
 * @MigrateSource(
 *   id = "gitlab_user",
 *   source_module = "gitlab_time_tracker_migration"
 * )
 */
class GitlabUserSource extends SourcePluginBase {
  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $results = \Drupal::service('gitlab_time_tracker.gitlab')->fetchUsers();
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
    ];
  }

  public function __toString() {
    // This could cause a problem when using a lot of urls, may need to hash.
    return implode(',', $this->fields());
  }

}
