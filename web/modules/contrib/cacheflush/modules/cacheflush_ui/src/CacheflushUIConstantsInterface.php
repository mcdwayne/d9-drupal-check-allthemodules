<?php

namespace Drupal\cacheflush_ui;

/**
 * Interface CacheflushUIConstantsInterface.
 *
 * @package Drupal\cacheflush_ui
 */
interface CacheflushUIConstantsInterface {

  /**
   * Denotes that the cacheflush is not published.
   */
  const CACHEFLUSH_NOT_PUBLISHED = 0;

  /**
   * Denotes that the cacheflush is published.
   */
  const CACHEFLUSH_PUBLISHED = 1;

  /**
   * Denotes that the cacheflush entity has no menu entry.
   */
  const CACHEFLUSH_NO_MENU = 0;

  /**
   * Denotes that the cacheflush entity has menu entry.
   */
  const CACHEFLUSH_MENU = 1;

  /**
   * Denotes that the cacheflush entity has cron entry.
   */
  const CACHEFLUSH_CRON = 1;

}
