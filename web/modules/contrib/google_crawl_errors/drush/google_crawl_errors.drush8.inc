<?php

/**
 * @file
 * Drush integration for the google_crawl_errors module.
 */

use Drupal\google_crawl_errors\GoogleCrawlErrors;

/**
 * Implements hook_drush_command().
 */
function google_crawl_errors_drush_command() {
  $items = [];

  $items['get-crawl-errors'] = [
    'description' => 'Get Google Search Console crawl errors. Go to https://developers.google.com/webmaster-tools/search-console-api-original/v3/urlcrawlerrorssamples/list for full list of valid arguments.',
    'arguments' => [
      'siteId' => 'The unique site id. For example: site1.',
      'siteUrl' => 'The site\'s URL, including protocol. For example: http://www.example.com/.',
      'category' => 'The crawl error category. For example: authPermissions.',
      'platform' => 'The user agent type (platform) that made the request. For example: web',
    ],
    'examples' => [
      'get-crawl-errors site1 https://www.example.com/ notFound web' => 'Get list of 404 errors for web platform.',
    ],
    'drupal dependencies' => ['google_crawl_errors'],
  ];
  return $items;
}

/**
 * Query Google Console API to get list of sample crawl errors.
 */
function drush_google_crawl_errors_get_crawl_errors($site_id, $site_url, $category, $platform, $gce = NULL) {
  if (!$gce) {
    $gce = new GoogleCrawlErrors();
  }
  $gce->updateResultData($site_id, $site_url, $category, $platform);
}
