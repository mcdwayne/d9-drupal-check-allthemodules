<?php

/**
 * @file
 * Drush integration.
 */

use Drupal\Core\Database\Database;

/**
 * Implements hook_drush_command().
 */
function domain_simple_sitemap_drush_command() {
  $items['adas-generate'] = [
    'description' => 'Regenerate XML sitemaps for all languages and domains according to the module settings.',
    'callback' => 'drush_domain_simple_sitemap_generate',
    'drupal dependencies' => ['simple_sitemap', 'domain_simple_sitemap'],
  ];
  return $items;
}

/**
 * Callback function for hook_drush_command().
 *
 * Regenerate sitemap for all languages.
 */
function drush_domain_simple_sitemap_generate() {
  // Remove all existing rows and add one row in table for every domain.
  Database::getConnection()->truncate('simple_sitemap')->execute();
  $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
  foreach ($domains as $domain_id => $domain) {
    \Drupal::service('domain_simple_sitemap.sitemap_generator')->generateSitemap([], FALSE, $domain_id);
  }
}
