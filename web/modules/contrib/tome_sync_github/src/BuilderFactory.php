<?php

namespace Drupal\tome_sync_github;

use Drupal\Core\Site\Settings;
use Vijaycs85\GithubPublisher\Builder;
use Vijaycs85\GithubPublisher\Repository;

/**
 * Provides a factory for creating builder objects.
 */
class BuilderFactory {

  /**
   * Returns a Builder object.
   *
   * @return \Vijaycs85\GithubPublisher\Builder
   *   Builder object.
   *
   * @throws \Exception
   */
  public static function getBuilder() {
    $token = Settings::get('tome_sync_github_token');
    if (!$token) {
      throw new \Exception('Please set github token.');
    }

    $config = \Drupal::config('tome_sync_github.settings');
    $repository = new Repository($config->get('github.name', $token));
    return new Builder($config->get('build_directory'), $repository);
  }

}
