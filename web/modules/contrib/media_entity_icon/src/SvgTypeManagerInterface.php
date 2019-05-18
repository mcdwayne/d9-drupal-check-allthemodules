<?php

namespace Drupal\media_entity_icon;

/**
 * Provides an interface for SvgTypeManager.
 *
 * @package Drupal\media_entity_icon
 */
interface SvgTypeManagerInterface {

  /**
   * Get icon bundle names fetched by IDs.
   *
   * @param bool $reset
   *   Whether it should purge the static cache or not.
   *
   * @return array
   *   Icon bundles names fetched by bundle ID.
   */
  public function getIconBundleNames($reset = FALSE);

  /**
   * Get icon bundles configuration.
   *
   * @param bool $reset
   *   Whether it should purge the static cache or not.
   *
   * @return array
   *   Icon bundles config fetch by bundle ID.
   */
  public function getIconBundleConfigs($reset = FALSE);

  /**
   * Get icon bundle configuration.
   *
   * @param string $bundle_id
   *   Icon bundle ID.
   * @param bool $reset
   *   Whether it should purge the static cache or not.
   *
   * @return array
   *   Bundle configuration.
   */
  public function getIconBundleConfig($bundle_id, $reset = FALSE);

  /**
   * Get icon bundle source field.
   *
   * @param string $bundle_id
   *   Icon bundle ID.
   * @param bool $reset
   *   Whether it should purge the static cache or not.
   *
   * @return string
   *   Source field name or null if none found.
   */
  public function getIconBundleSourceField($bundle_id, $reset = FALSE);

  /**
   * Get icon bundle id field.
   *
   * @param string $bundle_id
   *   Icon bundle ID.
   * @param bool $reset
   *   Whether it should purge the static cache or not.
   *
   * @return string
   *   ID field name or null if none found.
   */
  public function getIconBundleIdField($bundle_id, $reset = FALSE);

  /**
   * Get sprite bundle names fetched by ids.
   *
   * @param bool $reset
   *   Whether it should purge the static cache or not.
   *
   * @return array
   *   Sprite bundles names fetched by bundle ID.
   */
  public function getSpriteBundleNames($reset = FALSE);

}
