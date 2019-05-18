<?php

namespace Drupal\google_crawl_errors\Commands;

use Drupal\google_crawl_errors\GoogleCrawlErrors;
use Drush\Commands\DrushCommands;

/**
 * Provides drush commands for google crawl errors.
 */
class GoogleCrawlErrorsCommands extends DrushCommands {

  /**
   * Query Google Console API to get list of sample crawl errors.
   * Go to https://developers.google.com/webmaster-tools/search-console-api-original/v3/urlcrawlerrorssamples/list
   * for full list of valid arguments.
   *
   * @param string $site_id
   *   The unique site id. For example: site1.
   * @param string $site_url
   *   The site's URL, including protocol. For example: http://www.example.com/.
   * @param string $category
   *   The crawl error category. For example: authPermissions.
   * @param string $platform
   *   The user agent type (platform) that made the request. For example: web
   *
   * @command google_crawl_errors:getCrawlErrors
   *
   * @aliases get-crawl-errors
   *
   * @usage google_crawl_errors:getCrawlErrors site1 https://www.example.com/ notFound web
   *   Get list of 404 errors for web platform.
   */
  public function getCrawlErrors($site_id, $site_url, $category, $platform, $gce = NULL) {
    if (!$gce) {
      $gce = new GoogleCrawlErrors();
    }
    $gce->updateResultData($site_id, $site_url, $category, $platform);
  }
}
