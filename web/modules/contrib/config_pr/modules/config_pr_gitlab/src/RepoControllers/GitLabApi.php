<?php

namespace Drupal\config_pr_gitlab\RepoControllers;

use Gitlab\Api\AbstractApi;

/**
 * Extends AbstractApi to allow doing extra queries against gitlab endpoints.
 */
class GitLabApi extends AbstractApi {
  /**
   * {@inheritdoc}
   */
  public function get($path, array $parameters = [], $requestHeaders = []) {
    return parent::get($path,  $parameters ,  $requestHeaders );
  }
}
