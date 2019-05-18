<?php

/**
 * @file
 * Page structure part of SEO.
 */

/**
 * Check availability of Pathauto.
 */
function bs_seo_page_structure_pathauto() {
  return Drupal::moduleHandler()->moduleExists('pathauto');
}

/**
 * Check availability of Metatag.
 */
function bs_seo_page_structure_metatag() {
  return Drupal::moduleHandler()->moduleExists('metatag');
}
