<?php

namespace Drupal\drd;

use Drupal\update\UpdateFetcher as CoreUpdateFetcher;

/**
 * Fetches project information from remote locations.
 */
class UpdateFetcher extends CoreUpdateFetcher {

  /**
   * {@inheritdoc}
   */
  public function buildFetchUrl(array $project, $site_key = '') {
    $name = $project['name'];
    $core = empty($project['core']) ? \Drupal::CORE_COMPATIBILITY : $project['core'];
    $url = $this->getFetchBaseUrl($project);
    $url .= '/' . $name . '/' . $core;
    return $url;
  }

}
