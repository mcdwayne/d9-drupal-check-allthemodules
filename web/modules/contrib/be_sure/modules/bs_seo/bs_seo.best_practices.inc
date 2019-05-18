<?php

/**
 * @file
 * Best practices part of SEO.
 */
use Drupal\Component\Utility\Unicode;

/**
 * Check availability of Search 404.
 */
function bs_seo_best_practices_search404() {
  return Drupal::moduleHandler()->moduleExists('search404');
}

/**
 * Check availability of Google Analytics.
 */
function bs_seo_best_practices_gag() {
  return Drupal::moduleHandler()->moduleExists('googleanalytics')
  && Unicode::strlen(Drupal::config('googleanalytics.settings')->get('googleanalytics_account')) > 3;
}

/**
 * Check availability of cache Google analytics.
 */
function bs_seo_best_practices_gag_cache() {
  return bs_seo_best_practices_gag()
  && Drupal::config('googleanalytics.settings')->get('googleanalytics_cache');
}

/**
 * Check availability of Site Verification.
 */
function bs_seo_best_practices_site_verify() {
  return Drupal::moduleHandler()->moduleExists('site_verify');
}
