<?php

/**
 * @file
 * User experience part of SEO.
 */

/**
 * Check availability of Site map.
 */
function bs_seo_user_experience_site_map() {
  return \Drupal::moduleHandler()->moduleExists('sitemap');
}

/**
 * Check availability of Scheduler.
 */
function bs_seo_user_experience_scheduler() {
  return \Drupal::moduleHandler()->moduleExists('scheduler');
}

/**
 * Check availability of Redirect.
 */
function bs_seo_user_experience_redirect() {
  return \Drupal::moduleHandler()->moduleExists('redirect');
}
