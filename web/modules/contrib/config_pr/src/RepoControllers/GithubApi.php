<?php

namespace Drupal\config_pr\RepoControllers;

use Github\Api\AbstractApi;

/**
 * Extends AbstractApi to allow doing extra queries against github endpoints.
 */
class GithubApi extends AbstractApi {
  /**
   * {@inheritdoc}
   */
  public function get($path, array $parameters = [], array $requestHeaders = []) {
    return parent::get($path,  $parameters ,  $requestHeaders );
  }
}
