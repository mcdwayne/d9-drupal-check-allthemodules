<?php

/**
 * @file
 * Checking callback of sure items.
 */

/**
 * Check availability of Fast 404.
 */
function bs_performance_module_fast_404() {
  return \Drupal::moduleHandler()->moduleExists('fast_404');
}

/**
 * Module Database logging should be disabled.
 */
function bs_performance_module_dblog() {
  return !\Drupal::moduleHandler()->moduleExists('dblog');
}

/**
 * Module Theme Developer should be disabled.
 */
function bs_performance_module_update() {
  return !\Drupal::moduleHandler()->moduleExists('update');
}

/**
 * Module Backup and Migrate should be disabled.
 */
function bs_performance_module_backup() {
  return !\Drupal::moduleHandler()->moduleExists('backup_migrate');
}

/**
 * Module Coder should be disabled.
 */
function bs_performance_module_coder() {
  return !\Drupal::moduleHandler()->moduleExists('coder');
}

/**
 * Module SimpleTests should be disabled.
 */
function bs_performance_module_simpletest() {
  return !\Drupal::moduleHandler()->moduleExists('simpletest');
}

/**
 * Check availability of Image Resize Filter.
 */
function bs_performance_module_image_resize_filter() {
  return \Drupal::moduleHandler()->moduleExists('image_resize_filter');
}

/**
 * Check availability of ImageAPI Optimize (or Image Optimize).
 */
function bs_performance_module_imageapi_optimize() {
  return \Drupal::moduleHandler()->moduleExists('imageapi_optimize');
}
