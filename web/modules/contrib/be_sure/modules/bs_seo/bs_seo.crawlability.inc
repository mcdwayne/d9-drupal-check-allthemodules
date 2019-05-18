<?php

/**
 * @file
 * Crawlability part of SEO.
 */

/**
 * Check availability of XML Sitemap.
 */
function bs_seo_crawlability_xmlsitemap() {
  return Drupal::moduleHandler()->moduleExists('xmlsitemap');
}

/**
 * Check availability of RobotsTxt.
 */
function bs_seo_crawlability_robotstxt() {
  return Drupal::moduleHandler()->moduleExists('robotstxt');
}
